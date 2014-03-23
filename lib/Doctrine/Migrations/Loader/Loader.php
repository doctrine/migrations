<?php

namespace Doctrine\Migrations\Loader;

/**
 * Load all migration versions in a given path.
 */
interface Loader
{
    /**
     * @param string $path
     *
     * @return \Doctrine\Migrations\MigrationSet
     */
    public function load($path);
}
