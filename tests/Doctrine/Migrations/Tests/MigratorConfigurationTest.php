<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\MigratorConfiguration;
use PHPUnit\Framework\TestCase;

class MigratorConfigurationTest extends TestCase
{
    private MigratorConfiguration $migratorConfiguration;

    public function testDryRun(): void
    {
        self::assertFalse($this->migratorConfiguration->isDryRun());

        $this->migratorConfiguration->setDryRun(true);

        self::assertTrue($this->migratorConfiguration->isDryRun());
    }

    public function testTimeAllQueries(): void
    {
        self::assertFalse($this->migratorConfiguration->getTimeAllQueries());

        $this->migratorConfiguration->setTimeAllQueries(true);

        self::assertTrue($this->migratorConfiguration->getTimeAllQueries());
    }

    public function testNoMigrationException(): void
    {
        self::assertFalse($this->migratorConfiguration->getNoMigrationException());

        $this->migratorConfiguration->setNoMigrationException(true);

        self::assertTrue($this->migratorConfiguration->getNoMigrationException());
    }

    public function testAllOrNothing(): void
    {
        self::assertFalse($this->migratorConfiguration->isAllOrNothing());

        $this->migratorConfiguration->setAllOrNothing(true);

        self::assertTrue($this->migratorConfiguration->isAllOrNothing());
    }

    protected function setUp(): void
    {
        $this->migratorConfiguration = new MigratorConfiguration();
    }
}
