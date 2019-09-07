<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Metadata;

use Doctrine\Migrations\AbstractMigration;

class MigrationPlanItem
{
    /** @var string */
    private $direction;
    /** @var MigrationInfo */
    private $info;

    /** @var AbstractMigration */
    private $migration;

    public function __construct(MigrationInfo $info, AbstractMigration $migration, string $direction)
    {
        $this->info      = $info;
        $this->migration = $migration;
        $this->direction = $direction;
    }

    public function getInfo() : MigrationInfo
    {
        return $this->info;
    }

    public function getMigration() : AbstractMigration
    {
        return $this->migration;
    }

    public function getDirection() : string
    {
        return $this->direction;
    }
}
