<?php
/**
 * Created by PhpStorm.
 * User: Zeljko
 * Date: 12/13/2018
 * Time: 5:46 PM
 */

// Root directory
define('ROOT', __DIR__.DIRECTORY_SEPARATOR);
// Application directory
define('APP', ROOT.'app'.DIRECTORY_SEPARATOR);

$config = parse_ini_file(APP.'config'.DIRECTORY_SEPARATOR.'config.ini', true);

// Database
define('DSN', 'mysql:host='.$config['database']['host'].';dbname='.$config['database']['name'].';charset=utf8mb4');
define('DB_USER', $config['database']['username']);
define('DB_PASS', $config['database']['password']);
define('DESC', 'DESC');
define('ASC', 'ASC');
define('RANDOM', 'RANDOM');

// Application params
define('ERROR_REPORTING', $config['application']['error_reporting']);
define('SOFT_DELETE', $config['application']['soft_delete']);

if(ERROR_REPORTING) {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
}

if(!SOFT_DELETE){
    session_start(); // undo option will be saved in session
}

// Autoloader
require_once APP.'core'.DIRECTORY_SEPARATOR.'Autoloader.php';
Autoloader::register();

// Router will handle all  requests
$router = new Router();
