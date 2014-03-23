<?php

namespace Doctrine\Migrations\Loader;

use Doctrine\Migrations\MigrationSet;

class ChainLoader implements Loader
{
    private $loaders = array();

    public function add(Loader $loader)
    {
        $this->loaders[] = $loader;
    }

    public function load($path)
    {
        $set = new MigrationSet();

        foreach ($this->loaders as $loader) {
            $loadedSet = $loader->load($path);

            foreach ($loadedSet as $migration) {
                $set->add($migration);
            }
        }

        return $set;
    }
}
