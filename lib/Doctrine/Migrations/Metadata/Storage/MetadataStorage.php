<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata\Storage;

use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Version\ExecutionResult;

interface MetadataStorage
{
    public function ensureInitialized(): void;

    /**
     * Returns only the migrations that have been successfully executed (reason=executed)
     */
    public function getExecutedMigrations(): ExecutedMigrationsList;

    /**
     * Returns all the for which there was an execution attempt
     * Includes skipped, errored executed migrations.
     */
    public function getAllExecutedMigrations(): ExecutedMigrationsList;

    public function complete(ExecutionResult $migration): void;

    public function reset(): void;
}
