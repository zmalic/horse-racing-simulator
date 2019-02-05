<?php
/**
 * Created by PhpStorm.
 * User: Zeljko
 * Date: 12/13/2018
 * Time: 7:04 PM
 */

class Autoloader
{
    private static $directories = [
        'controllers',
        'core',
        'libs',
        'models'
    ];

    /**
     * Simple autoloader for classes in app's $directories
     */
    public static function register()
    {
        spl_autoload_register(function ($class) {
            $file = $class.'.php';
            foreach(self::$directories as $directory) {
                $file = APP.$directory.DIRECTORY_SEPARATOR.$class.'.php';
                if(file_exists($file)) {
                    require_once($file);
                    return;
                }
            }

            // if class file not exists in Autoloader::$directories trow exception
            throw new Exception("Unable to load $file.");
        });
    }
}
