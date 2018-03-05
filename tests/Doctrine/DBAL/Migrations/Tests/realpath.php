<?php
namespace Doctrine\DBAL\Migrations;

/**
 * Override realpath() in current namespace for testing since it
 * has issues with vfsStream
 *
 * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues
 *
 * @param string $path
 *
 * @return string|false
 */
function realpath($path)
{
    if (strpos($path, 'vfs://') === 0) {
        return $path;
    }

    return \realpath($path);
}
