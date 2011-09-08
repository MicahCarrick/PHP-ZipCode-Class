PHP Zip Code Range and Distance Calculation
===========================================

**Calculate the distance between U.S. zip codes and find all zip codes within a 
given distance of a known zip code.**

This project was started as a port to PHP 5 of a zip code class I wrote in 2005 
for PHP 4. It also provides improvements based on suggestions from users of 
the original code.


Zip Code Database
-----------------

The `ZipCode` class is based on a MySQL table or view with the following fields:

    zip_code_id      int(11) PRIMARY KEY
    zip_code         varchar(5) UNIQUE KEY
    city             varchar(50)
    county           varchar(50)
    state_name       varchar(50)
    state_prefix     varchar(2)
    area_code        varchar(3)
    time_zone        varchar(50)
    lat              float
    lon              float

While the name of this table can be specified by the `mysql_table` class property,
the default table name is `zip_code`.

** Original Database (obsolete) **

The original zip code database was derived from 2000 U.S. Census data and manually
tweaked over the years when a zip code was missing or incorrect. This database
is known to have some missing and inaccurate zip codes. 

You can find the SQL script with the entire `zip_code` table in 
`/data/obsolute/zip_code.sql`. If you do not have access to your database from 
the command line, such as using phpMyAdmin, you will have to split the script
into multiple files and upload them one at a time.

** New Databases **

There are numerous sources for U.S. zip code databases. Some are free and some 
can be purchased. You can use one of these databases by either copying the 
necessary fields from the source table to the `zip_code` table using a 
[`SELECT INTO`][5] statement or by creating a view with the [`CREATE VIEW`][6]
statement.

[5]: http://dev.mysql.com/doc/refman/5.0/en/ansi-diff-select-into-table.html
[6]: http://dev.mysql.com/doc/refman/5.0/en/create-view.html


Live Demo
---------

See `example.php` for example usage. You can see a live demo on my personal 
website (uses the obsolete data): [PHP-ZipCode Example][3].

[3]: http://www.micahcarrick.com/code/PHP-ZipCode/example.php


License
-------

[GNU General Public License v3][4]

[4]: http://opensource.org/licenses/gpl-3.0.html
