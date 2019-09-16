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
    private $executedOn;

    /**
     * Milliseconds
     *
     * @var int
     */
    public $executionTime;

    public function __construct(Version $version, ?DateTime $executedOn, ?int $executionTime)
    {
        $this->version       = $version;
        $this->executedOn    = $executedOn;
        $this->executionTime = $executionTime;
    }

    public function getExecutionTime() : ?int
    {
        return $this->executionTime;
    }

    public function getExecutedOn() : ?DateTime
    {
        return $this->executedOn;
    }

    public function getVersion()
    {
        return $this->version;
    }
}
