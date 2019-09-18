<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use DateTime;
use Doctrine\Migrations\Version\Version;

class ExecutedMigration
{
    /** @var Version */
    private $version;

    /** @var DateTime */
    private $executedAt;

    /**
     * Milliseconds
     *
     * @var int
     */
    public $executionTime;

    public function __construct(Version $version, ?DateTime $executedAt = null, ?int $executionTime = null)
    {
        $this->version = $version;
        $this->executedAt = $executedAt;
        $this->executionTime = $executionTime;
    }

    public function getExecutionTime(): ?int
    {
        return $this->executionTime;
    }

    public function getExecutedAt(): ?DateTime
    {
        return $this->executedAt;
    }

    public function getVersion()
    {
        return $this->version;
    }
}
