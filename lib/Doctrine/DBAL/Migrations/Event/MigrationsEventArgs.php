<?php

namespace Doctrine\DBAL\Migrations\Event;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

class MigrationsEventArgs extends EventArgs
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * The direction of the migration.
     *
     * @var string (up|down)
     */
    private $direction;

    /**
     * Whether or not the migrations are executing in dry run mode.
     *
     * @var bool
     */
    private $dryRun;

    public function __construct(Configuration $config, $direction, $dryRun)
    {
        $this->config    = $config;
        $this->direction = $direction;
        $this->dryRun    = (bool) $dryRun;
    }

    public function getConfiguration()
    {
        return $this->config;
    }

    public function getConnection()
    {
        return $this->config->getConnection();
    }

    public function getDirection()
    {
        return $this->direction;
    }

    public function isDryRun()
    {
        return $this->dryRun;
    }
}
