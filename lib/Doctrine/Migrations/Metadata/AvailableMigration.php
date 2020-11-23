<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\Version;

/**
 * Available migrations may or may not be already executed
 * The migration might be already executed or not.
 */
final class AvailableMigration
{
    /** @var Version */
    private $version;

    /** @var AbstractMigration */
    private $migration;

    public function __construct(Version $version, AbstractMigration $migration)
    {
        $this->version   = $version;
        $this->migration = $migration;
    }

    public function getVersion(): Version
    {
        return $this->version;
    }

    public function getMigration(): AbstractMigration
    {
        return $this->migration;
    }
}
