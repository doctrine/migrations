<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\Version;

class AvailableMigration
{
    /**
     * @var Version
     */
    private $version;

    /**
     * @var AbstractMigration
     */
    private $migration;

    public function __construct(Version $version, AbstractMigration $migration)
    {

        $this->version = $version;
        $this->migration = $migration;
    }

    /**
     * @return Version
     */
    public function getVersion(): Version
    {
        return $this->version;
    }

    /**
     * @return AbstractMigration
     */
    public function getMigration(): AbstractMigration
    {
        return $this->migration;
    }


}
