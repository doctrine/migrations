<?php
namespace Doctrine\DBAL\Migrations;

/**
 * Override realpath() in current namespace for testing since it
 * has issues with vfsStream
 *
 * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues
 *
 * @param $path
 *
 * @return string|false
 */
function realpath($path)
{
    if (0 === strpos($path, 'vfs://')) {
        return $path;
    }

    return \realpath($path);
}
