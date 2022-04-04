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
    private Version $version;

    private ?DateTimeImmutable $executedAt = null;

    /**
     * Seconds
     */
    public ?float $executionTime = null;

    public function __construct(Version $version, ?DateTimeImmutable $executedAt = null, ?float $executionTime = null)
    {
        $this->version       = $version;
        $this->executedAt    = $executedAt;
        $this->executionTime = $executionTime;
    }

    public function getExecutionTime(): ?float
    {
        return $this->executionTime;
    }

    public function getExecutedAt(): ?DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }
}
