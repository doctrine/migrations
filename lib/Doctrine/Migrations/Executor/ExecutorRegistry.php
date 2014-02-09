<?php

namespace Doctrine\Migrations\Executor;

use Doctrine\Migrations\MigrationCollection;

class ExecutorRegistry
{
    public function findFor(MigrationCollection $collection)
    {
        return array();
    }
}
