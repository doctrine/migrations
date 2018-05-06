<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use function strpos;

/**
 * Override realpath() in current namespace for testing since it
 * has issues with vfsStream
 *
 * @see https://github.com/mikey179/vfsStream/wiki/Known-Issues
 *
 * @return string|false
 */
function realpath(string $path)
{
    if (strpos($path, 'vfs://') === 0) {
        return $path;
    }

    return \realpath($path);
}
