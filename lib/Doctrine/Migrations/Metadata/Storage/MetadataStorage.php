<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata\Storage;

use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Doctrine\Migrations\Version\ExecutionResult;

interface MetadataStorage
{
    public function getExecutedMigrations() : ExecutedMigrationsSet;

    public function complete(ExecutionResult $migration) : void;

    public function reset() : void;
}
