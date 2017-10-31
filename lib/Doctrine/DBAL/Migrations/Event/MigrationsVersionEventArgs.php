<?php

namespace Doctrine\DBAL\Migrations\Event;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Version;

class MigrationsVersionEventArgs extends MigrationsEventArgs
{
    /**
     * The version the event pertains to.
     *
     * @var Version
     */
    private $version;

    public function __construct(Version $version, Configuration $config, $direction, $dryRun)
    {
        parent::__construct($config, $direction, $dryRun);
        $this->version = $version;
    }

    public function getVersion()
    {
        return $this->version;
    }
}
