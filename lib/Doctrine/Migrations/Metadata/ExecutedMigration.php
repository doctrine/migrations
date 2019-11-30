<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use DateTimeImmutable;
use Doctrine\Migrations\Version\Version;

final class ExecutedMigration
{
    /** @var Version */
    private $version;

    /** @var DateTimeImmutable|null */
    private $executedAt;

    /**
     * Milliseconds
     *
     * @var int|null
     */
    public $executionTime;

    public function __construct(Version $version, ?DateTimeImmutable $executedAt = null, ?int $executionTime = null)
    {
        $this->version       = $version;
        $this->executedAt    = $executedAt;
        $this->executionTime = $executionTime;
    }

    public function getExecutionTime() : ?int
    {
        return $this->executionTime;
    }

    public function getExecutedAt() : ?DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function getVersion() : Version
    {
        return $this->version;
    }
}
