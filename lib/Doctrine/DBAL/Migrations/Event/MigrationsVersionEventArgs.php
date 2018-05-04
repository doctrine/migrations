<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Event;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Version;

class MigrationsVersionEventArgs extends MigrationsEventArgs
{
    /** @var Version */
    private $version;

    public function __construct(
        Version $version,
        Configuration $config,
        string $direction,
        bool $dryRun
    ) {
        parent::__construct($config, $direction, $dryRun);

        $this->version = $version;
    }

    public function getVersion() : Version
    {
        return $this->version;
    }
}
