<?php
use Model\Utilities\Log;
use Model\Services\DataAccess;

// - - - - - [ INIT ]

if(!file_exists('./app/errors'))
{
    mkdir('./app/errors', 0777, true);    
}

define('APP_ROOT', dirname(dirname(__FILE__)));
define('DB', APP_ROOT . '/db/db.sqlite');
define('ERRORS', APP_ROOT . '/errors');
define('GMT', '+3'); // << - - - SET YOUR TIMEZONE HERE
define('APP_NAME', 'Map Demo'); // << - - - SET YOUR APP'S NAME HERE

DataAccess::$pdo = new PDO('sqlite:' . DB);
DataAccess::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

?>