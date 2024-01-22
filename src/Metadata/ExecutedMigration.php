<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use DateTimeImmutable;
use Doctrine\Migrations\Version\Version;

/**
 * Represents an already executed migration.
 * The migration might be not available anymore.
 */
final class ExecutedMigration
{
    public function __construct(
        private readonly Version $version,
        private readonly DateTimeImmutable|null $executedAt = null,
        public float|null $executionTime = null,
    ) {
    }

    public function getExecutionTime(): float|null
    {
        return $this->executionTime;
    }

    public function getExecutedAt(): DateTimeImmutable|null
    {
        return $this->executedAt;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }
}
