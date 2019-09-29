<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Tests\MigrationTestCase;
use ReflectionProperty;

abstract class AbstractConfigurationTest extends MigrationTestCase
{
    public function testFinderIsIncompatibleWithConfiguration() : void
    {
        $this->markTestSkipped();
        $this->expectException(MigrationException::class);

        $this->loadConfiguration('organize_by_year', null, new GlobFinder());
    }

    public function testSetMigrationFinder() : void
    {
        $this->markTestSkipped();
        $migrationFinderProphecy = $this->prophesize(MigrationFinder::class);
        /** @var MigrationFinder $migrationFinder */
        $migrationFinder = $migrationFinderProphecy->reveal();

        $config = $this->loadConfiguration();
        $config->setMigrationsFinder($migrationFinder);

        $migrationFinderPropertyReflected = new ReflectionProperty(
            Configuration::class,
            'migrationFinder'
        );
        $migrationFinderPropertyReflected->setAccessible(true);
        self::assertSame($migrationFinder, $migrationFinderPropertyReflected->getValue($config));
    }

    public function testVersionsOrganizationIncompatibleFinder() : void
    {
        $this->markTestSkipped();
        $this->expectException(MigrationException::class);

        $config = $this->loadConfiguration('organize_by_year_and_month');
        $config->setMigrationsFinder(new GlobFinder());
    }
}
