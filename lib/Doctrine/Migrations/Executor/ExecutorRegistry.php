<?php

namespace Doctrine\Migrations\Executor;

use Doctrine\Migrations\MigrationInfo;

interface ExecutorRegistry
{
    public function findFor(MigrationInfo $migration);
}
