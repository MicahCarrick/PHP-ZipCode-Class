<?php
/**
 *  @package    zipcode
 */

/**
 *  Zip Code Range and Distance Calculation
 *
 *  Calculate the distance between zip codes and find all zip codes within a 
 *  given distance of a known zip code.
 *
 *  Project page: https://github.com/Quixotix/PHP-ZipCode-Class
 *  Live example: http://www.micahcarrick.com/code/PHP-ZipCode/example.php
 *  
 *  @package    zipcode
 *  @author     Micah Carrick
 *  @copyright  (c) 2011 - Micah Carrick
 *  @version    2.0
 *  @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License v3
 */
class ZipCode
{
    private $zip_code_id;
    private $zip_code;
    private $lat;
    private $lon;
    private $city;
    private $county;
    private $area_code;
    private $time_zone;
    private $state_prefix;
    private $state_name;

    public $mysql_table = 'zip_code';
    public $mysql_conn = false;
    private $mysql_row;
    
    private $print_name;
    private $location_type;
    
    const UNIT_MILES = 1;
    const UNIT_KILOMETERS = 2;
    const MILES_TO_KILOMETERS = 1.609344;
    
    const LOCATION_ZIP = 1;
    const LOCATION_CITY_STATE = 2;
    
    /**
     *  Constructor
     *
     *  Instantiate a new ZipCode object by passing in a location. The location
     *  can be specified by a string containing a 5-digit zip code, city and 
     *  state, or latitude and longitude.
     *
     *  @param  string
     *  @return ZipCode
     */
    public function __construct($location) 
    {
        if (is_array($location)) {
            $this->setPropertiesFromArray($location);
            $this->print_name = $this->zip_code;
            $this->location_type = $this::LOCATION_ZIP;
        } else {
            $this->location_type = $this->locationType($location);
            
            switch ($this->location_type) {
            
                case ZipCode::LOCATION_ZIP:
                    $this->zip_code = $this->sanitizeZip($location);
                    $this->print_name = $this->zip_code;
                    break;
                    
                case ZipCode::LOCATION_CITY_STATE:
                    $a = $this->parseCityState($location);
                    $this->city = $a[0];
                    $this->state_prefix = $a[1];
                    $this->print_name = $this->city;
                    break;
                    
                default:
                    throw new Exception('Invalid location type for '.__CLASS__);
            }
        }
    }

    public function __toString()
    {
        return $this->print_name;
    }
    
    /**
    *   Calculate Distance using SQL
    *
    *   Calculates the distance, in miles, to a specified location using MySQL
    *   math functions within the query.
    *
    *   @access private
    *   @param  string
    *   @return float
    */
    private function calcDistanceSql($location)
    {
        $sql = 'SELECT 3956 * 2 * ATAN2(SQRT(POW(SIN((RADIANS(t2.lat) - '
              .'RADIANS(t1.lat)) / 2), 2) + COS(RADIANS(t1.lat)) * '
              .'COS(RADIANS(t2.lat)) * POW(SIN((RADIANS(t2.lon) - '
              .'RADIANS(t1.lon)) / 2), 2)), '
              .'SQRT(1 - POW(SIN((RADIANS(t2.lat) - RADIANS(t1.lat)) / 2), 2) + '
              .'COS(RADIANS(t1.lat)) * COS(RADIANS(t2.lat)) * '
              .'POW(SIN((RADIANS(t2.lon) - RADIANS(t1.lon)) / 2), 2))) '
              .'AS "miles" '
              ."FROM {$this->mysql_table} t1 INNER JOIN {$this->mysql_table} t2 ";
        
        
        switch ($this->location_type) {
        
            case ZipCode::LOCATION_ZIP:
                // note: zip code is sanitized in the constructor
                $sql .= "WHERE t1.zip_code = '{$this->zip_code}' ";
                break;
                
            case ZipCode::LOCATION_CITY_STATE:
                $city = @mysql_real_escape_string($this->city);
                $state = @mysql_real_escape_string($this->state_prefix);
                $sql .= "WHERE (t1.city = '$city' AND t1.state_prefix = '$state') AND t2.zip_code = '$zip_to'";
                break;
                
            default:
                throw new Exception('Invalid location type for '.__CLASS__);
        }

        switch (ZipCode::locationType($location))
        {
            case ZipCode::LOCATION_ZIP:
                $zip_to = $this->sanitizeZip($location);
                $sql .= "AND t2.zip_code = '$zip_to'";
                break;
            case ZipCode::LOCATION_CITY_STATE:
                $a = $this->parseCityState($location);
                $city = @mysql_real_escape_string($a[0]);
                $state = @mysql_real_escape_string($a[1]);
                $sql .= "AND (t2.city = '$city' AND t2.state_prefix = '$state')";
                break;
        }

        $r = @mysql_query($sql);
        
        if (!$r) {
            throw new Exception(mysql_error());
        }
        
        if (mysql_num_rows($r) == 0) {
            throw new Exception("Record does not exist calculating distance between $zip_from and $zip_to");
        }
        
        $miles = mysql_result($r, 0);
        mysql_free_result($r);
        
        return $miles;
    }
    
    public function getAreaCode()
    {
        if (empty($this->zip_code_id)) $this->setPropertiesFromDb();
        return $this->city;
    }
    
    public function getCity()
    {
        if (empty($this->zip_code_id)) $this->setPropertiesFromDb();
        return $this->city;
    }
    
    public function getCounty()
    {
        if (empty($this->zip_code_id)) $this->setPropertiesFromDb();
        return $this->county;
    }
    
    public function getStateName()
    {
        if (empty($this->zip_code_id)) $this->setPropertiesFromDb();
        return $this->state_name;
    }
    
    public function getStatePrefix()
    {
        if (empty($this->zip_code_id)) $this->setPropertiesFromDb();
        return $this->state_prefix;
    }
    
    public function getDbRow()
    {
        if (empty($this->zip_code_id)) $this->setPropertiesFromDb();
        return $this->mysql_row;
    }
    
    /**
    *   Get Distance To Zip
    *
    *   Gets the distance to another zip code. The distance can be obtained in
    *   either miles or kilometers.
    *
    *   @param  string
    *   @param  integer
    *   @param  integer
    *   @return float
    */
    public function getDistanceTo($zip, $units=ZipCode::UNIT_MILES)
    {
        $miles = $this->calcDistanceSql($zip);
        
        if ($units == ZipCode::UNIT_KILOMETERS) {
            return $miles * ZipCode::MILES_TO_KILOMETERS;
        } else {
            return $miles;
        }
    }
    
    public function getZipsInRange($range_from, $range_to, $units=1)
    {
        if (empty($this->zip_code_id)) $this->setPropertiesFromDb();
        
        $sql = "SELECT 3956 * 2 * ATAN2(SQRT(POW(SIN((RADIANS({$this->lat}) - "
              .'RADIANS(z.lat)) / 2), 2) + COS(RADIANS(z.lat)) * '
              ."COS(RADIANS({$this->lat})) * POW(SIN((RADIANS({$this->lon}) - "
              ."RADIANS(z.lon)) / 2), 2)), SQRT(1 - POW(SIN((RADIANS({$this->lat}) - "
              ."RADIANS(z.lat)) / 2), 2) + COS(RADIANS(z.lat)) * "
              ."COS(RADIANS({$this->lat})) * POW(SIN((RADIANS({$this->lon}) - "
              ."RADIANS(z.lon)) / 2), 2))) AS \"miles\", z.* FROM {$this->mysql_table} z "
              ."WHERE zip_code <> '{$this->zip_code}' " 
              ."AND lat BETWEEN ROUND({$this->lat} - (25 / 69.172), 4) "
              ."AND ROUND({$this->lat} + (25 / 69.172), 4) "
              ."AND lon BETWEEN ROUND({$this->lon} - ABS(25 / COS({$this->lat}) * 69.172)) "
              ."AND ROUND({$this->lon} + ABS(25 / COS({$this->lat}) * 69.172)) "
              ."AND 3956 * 2 * ATAN2(SQRT(POW(SIN((RADIANS({$this->lat}) - "
              ."RADIANS(z.lat)) / 2), 2) + COS(RADIANS(z.lat)) * "
              ."COS(RADIANS({$this->lat})) * POW(SIN((RADIANS({$this->lon}) - "
              ."RADIANS(z.lon)) / 2), 2)), SQRT(1 - POW(SIN((RADIANS({$this->lat}) - "
              ."RADIANS(z.lat)) / 2), 2) + COS(RADIANS(z.lat)) * "
              ."COS(RADIANS({$this->lat})) * POW(SIN((RADIANS({$this->lon}) - "
              ."RADIANS(z.lon)) / 2), 2))) <= $range_to "
              ."AND 3956 * 2 * ATAN2(SQRT(POW(SIN((RADIANS({$this->lat}) - "
              ."RADIANS(z.lat)) / 2), 2) + COS(RADIANS(z.lat)) * "
              ."COS(RADIANS({$this->lat})) * POW(SIN((RADIANS({$this->lon}) - "
              ."RADIANS(z.lon)) / 2), 2)), SQRT(1 - POW(SIN((RADIANS({$this->lat}) - "
              ."RADIANS(z.lat)) / 2), 2) + COS(RADIANS(z.lat)) * "
              ."COS(RADIANS({$this->lat})) * POW(SIN((RADIANS({$this->lon}) - "
              ."RADIANS(z.lon)) / 2), 2))) >= $range_from "
              ."ORDER BY 1 ASC";

        $r = mysql_query($sql);
        if (!$r) {
            throw new Exception(mysql_error());
        }
        $a = array();
        while ($row = mysql_fetch_array($r, MYSQL_ASSOC))
        {
            // TODO: load ZipCode from array
            $a[$row['miles']] = new ZipCode($row);
        }
        
        return $a;
    }

    private function hasDbConnection()
    {
        if ($this->mysql_conn) {
            return mysql_ping($this->mysql_conn);
        } else {
            return mysql_ping();
        }
    }
    
    

    private function locationType($location)
    {
        if (ZipCode::isValidZip($location)) {
            return ZipCode::LOCATION_ZIP;
        } elseif (ZipCode::isValidCityState($location)) {
            return ZipCode::LOCATION_CITY_STATE;
        } else {
            return false;
        }
    }
    
    static function isValidZip($zip)
    { 
        return preg_match('/^[0-9]{5}/', $zip);
    }
    
    static function isValidCityState($location)
    { 
        $words = split(',', $location);

        if (empty($words) || count($words) != 2 || strlen(trim($words[1])) != 2) {
            return false;
        }
        
        if (!is_numeric($words[0]) && !is_numeric($words[1]))  {
            return true;
        }

        return false;
    }
    
    static function parseCityState($location)
    {
        $words = split(',', $location);
        
        if (empty($words) || count($words) != 2 || strlen(trim($words[1])) != 2) {
            throw new Exception("Failed to parse city and state from string.");
        }
        
        $city = trim($words[0]);
        $state = trim($words[1]);
        
        return array($city, $state);
    }
    
    // @access protected
    private function sanitizeZip($zip)
    {
        return preg_replace("/[^0-9]/", '', $zip);
    }
    
    private function setPropertiesFromArray($a)
    {    
        if (!is_array($a)) {
            throw new Exception("Argument is not an array");
        }
        
        foreach ($a as $key => $value)
        {
            $this->$key = $value;
        }
        
        $this->mysql_row = $a;
    }
    
    private function setPropertiesFromDb()
    {
        switch ($this->location_type) {
        
            case ZipCode::LOCATION_ZIP:
                $sql = "SELECT * FROM {$this->mysql_table} t "
                      ."WHERE zip_code = '{$this->zip_code}' LIMIT 1";
                break;
                
            case ZipCode::LOCATION_CITY_STATE:
                $sql = "SELECT * FROM {$this->mysql_table} t "
                      ."WHERE city = '{$this->city}' "
                      ."AND state_prefix = '{$this->state_prefix}' LIMIT 1";
                break;
        }
        
        $r = mysql_query($sql);
        $row = mysql_fetch_array($r, MYSQL_ASSOC);
        mysql_free_result($r);
        
        if (!$row)
        {
            throw new Exception("{$this->print_name} was not found in the database.");
        }
        
        $this->setPropertiesFromArray($row);
    }
}

?>
