<?php

namespace Doctrine\DBAL\Migrations\Tests;

class Helper
{

    /**
     * Delete a directory.
     *
     * @see http://stackoverflow.com/a/8688278/1645517
     *
     * @param string $path
     * @return bool
     */
    public static function deleteDir($path)
    {
        if ('' === $path) {
            return false;
        }
        $class_func = [__CLASS__, __FUNCTION__];

        return is_file($path) ?
            @unlink($path) :
            array_map($class_func, glob($path . '/*')) === @rmdir($path);
    }
}
