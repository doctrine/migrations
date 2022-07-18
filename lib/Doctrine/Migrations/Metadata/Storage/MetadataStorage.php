<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata\Storage;

use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Version\ExecutionResult;

interface MetadataStorage
{
    public function ensureInitialized(): void;

    public function getExecutedMigrations(): ExecutedMigrationsList;

    public function complete(ExecutionResult $result, bool $dry_run = false) : array;

    public function reset(): void;
}
