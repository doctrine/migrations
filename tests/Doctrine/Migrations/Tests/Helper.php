<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use function array_map;
use function glob;
use function is_file;
use function rmdir;
use function unlink;

class Helper
{
    /**
     * Delete a directory.
     *
     * @see http://stackoverflow.com/a/8688278/1645517
     */
    public static function deleteDir(string $path) : bool
    {
        if ($path === '') {
            return false;
        }
        $class_func = [self::class, __FUNCTION__];

        return is_file($path) ?
            @unlink($path) :
            array_map($class_func, glob($path . '/*')) === @rmdir($path);
    }
}
