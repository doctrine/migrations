<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\MigrationPlanList;

/**
 * The MigrationPlanCalculator is responsible for calculating the plan for migrating from the current
 * version to another version.
 */
interface MigrationPlanCalculator
{
    /**
     * @param Version[] $versions
     */
    public function getPlanForVersions(array $versions, string $direction) : MigrationPlanList;

    public function getPlanUntilVersion(Version $to) : MigrationPlanList;

    /**
     * Returns a sorted list of migrations.
     */
    public function getMigrations() : AvailableMigrationsList;
}
