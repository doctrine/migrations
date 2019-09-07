<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use DateTime;
use Doctrine\Migrations\Version\ExecutionResult;
use Doctrine\Migrations\Version\Version;

class MigrationInfo
{
    /** @var Version */
    private $version;

    /** @var DateTime */
    private $executedOn;

    /** @var ExecutionResult */
    public $result;

    public function __construct(Version $version)
    {
        $this->version = $version;

        //@todo
        $this->executedOn = new DateTime();
    }

    public function getResult() : ?ExecutionResult
    {
        return $this->result;
    }

    public function setResult(ExecutionResult $result) : void
    {
        $this->result = $result;
    }

    public function getExecutedOn() : DateTime
    {
        return $this->executedOn;
    }

    public function setExecutedOn(DateTime $executedOn) : void
    {
        $this->executedOn = $executedOn;
    }

    public function getVersion()
    {
        return $this->version;
    }
}
