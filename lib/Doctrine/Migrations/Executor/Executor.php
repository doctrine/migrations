<?php

namespace Doctrine\Migrations\Executor;

use Doctrine\Migrations\MigrationInfo;

interface Executor
{
    public function execute(MigrationInfo $migration);
}
