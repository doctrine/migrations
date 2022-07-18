<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata\Storage;

use Doctrine\Migrations\Metadata\ExecutedMigrationsList;
use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Version\ExecutionResult;

interface MetadataStorage
{
    public function ensureInitialized(): void;

    public function getExecutedMigrations(): ExecutedMigrationsList;

    /**
     * @return Query[]
     */
    public function complete(ExecutionResult $result, bool $dryRun = false): array;

    public function reset(): void;
}
