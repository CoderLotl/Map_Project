<?php
namespace Model\Utilities;

use Model\Services\AuthJWT;
use Model\Services\DataAccessMDB;

/**
 * [Description Log]
 * Utility class. Creates, erases, and deletes .txt files. Useful for when you have to log some message error B) 
 */
class Log
{
    /**
     * Creates a .txt file or either appends content to one already existing with whatever text content the function is provided with.
     * @param string $path
     * @param mixed $content
     * 
     * @return bool True if it's successful, false if it fails for whatever reason.
     */
    public static function WriteLog(string $path, $content)
    {
        date_default_timezone_set('Etc/GMT' . GMT);

        $file = fopen(ERRORS . '/' . $path, 'a+');
        fwrite($file, $content . " ||| TIME: " . date("Y-m-d h:i:sa") . "\n");
        fclose($file);
        if(file_exists($path))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * Erases all content in the provided file. You better know what you're doing wit this thing.
     * @param mixed $file
     * 
     * @return void
     */
    public static function EraseLog($path)
    {
        file_put_contents(ERRORS . '/' . $path, '');
    }

    /**
     * Deletes the file at the provided path. Use this wisely.
     * @param mixed $path
     * 
     * @return void
     */
    public static function DeleteLog($path)
    {
        unlink(ERRORS . '/' . $path);
    }
}

?>