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
    /** @var Version */
    private $version;

    /** @var DateTimeImmutable|null */
    private $executedAt;

    /**
     * Seconds
     *
     * @var float|null
     */
    public $executionTime;

    /** @var string|null */
    private $reason;

    /** @var string|null */
    private $reasonDescription;

    public function __construct(
        Version $version,
        ?DateTimeImmutable $executedAt = null,
        ?float $executionTime = null,
        ?string $reason
    ) {
        $this->version       = $version;
        $this->executedAt    = $executedAt;
        $this->executionTime = $executionTime;
        $this->reason        = $reason;
    }

    public function getExecutionTime(): ?float
    {
        return $this->executionTime;
    }

    public function getExecutedAt(): ?DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function getReasonDescription(): ?string
    {
        return $this->reasonDescription;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }
}
