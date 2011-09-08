<html>
<head>
  <title>Zip Code Range and Distance Calculation Class for PHP 5</title>
</head>
<body>
<h1>Zip Code Range and Distance Calculation Class for PHP 5</h1>
<p>
    This is the example (example.php) shows how to calculate the distance between
    U.S. zip codes and find all zip codes within a distance from a known zip
    code.
</p>
<h3>More Information</h3>
<ul>
    <li><a 
    href="https://github.com/Quixotix/PHP-ZipCode-Class">PHP-ZipCode-Class</a>
    source code and downloads on Github.</li>
    <li>My blog post: <a
    href="http://www.micahcarrick.com/php5-zip-code-range-and-distance.html">PHP 5
    Zip Code Range and Distance Calculation</a>.</li>
</ul>
<?php

include('zipcode.php');

// connect to the MySQL database with the zip code table

mysql_connect('localhost', 'YOUR DB USER', 'YOUR PASSWORD');
mysql_select_db('YOUR DB NAME');


// you can instantiate ZipCode with a zip code or with city and state

$portland = new ZipCode("97214");
$ventura = new ZipCode("Ventura, CA");


/*
You can get the distance to another location by specifying a zip code, 
city/state string, or another ZipCode object. You can specify whether you want
to get the distance in miles or kilometers.
*/

echo "<h2>Get the distance between 2 zip codes</h2>";

$distance1 = round($portland->getDistanceTo("98501"), 2);
$distance2 = round($portland->getDistanceTo($ventura, ZipCode::UNIT_KILOMETERS), 2);
$distance3 = round($portland->getDistanceTo("Salem, OR"), 2);

echo "Zip code <strong>$portland</strong> is <strong>$distance1</strong> miles away from "
    ."zip code <strong>98501</strong><br/>";

echo "Zip code <strong>$portland</strong> is <strong>$distance2</strong> <em>kilometers</em> away from "
    ."the city <strong>$ventura</strong><br/>";

echo "Zip code <strong>$portland</strong> is <strong>$distance3</strong> miles away from "
    ."the city <strong>Salem</strong><br/>";


/*
You can get all of the zip codes within a distance range from teh zip. Here we
are doing all zip codes between 0 and 2 miles. The returned array contains the
distance as the array's key and the array element is another ZipCode object.
*/
echo "<h2>Get all zip codes between 10 and 15 miles from 97214</h2>";

foreach ($portland->getZipsInRange(10, 15) as $miles => $zip) {
    
    $miles = round($miles, 1);
    echo "Zip code <strong>$zip</strong> is <strong>$miles</strong> miles away from "
        ." <strong>$portland</strong> ({$zip->getCounty()} county)<br/>";
}

echo "<h2>Dump Database Row</h2>";

echo '<pre>';
print_r($portland->getDbRow());
echo '</pre>';

?>
</body>
</html>
