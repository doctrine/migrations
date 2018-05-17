<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\MigratorConfig;
use PHPUnit\Framework\TestCase;

class MigratorConfigTest extends TestCase
{
    /** @var MigratorConfig */
    private $migratorConfig;

    public function testDryRun() : void
    {
        self::assertFalse($this->migratorConfig->isDryRun());

        $this->migratorConfig->setDryRun(true);

        self::assertTrue($this->migratorConfig->isDryRun());
    }

    public function testTimeAllQueries() : void
    {
        self::assertFalse($this->migratorConfig->getTimeAllQueries());

        $this->migratorConfig->setTimeAllQueries(true);

        self::assertTrue($this->migratorConfig->getTimeAllQueries());
    }

    public function testNoMigrationException() : void
    {
        self::assertFalse($this->migratorConfig->getNoMigrationException());

        $this->migratorConfig->setNoMigrationException(true);

        self::assertTrue($this->migratorConfig->getNoMigrationException());
    }

    public function testAllOrNothing() : void
    {
        self::assertFalse($this->migratorConfig->isAllOrNothing());

        $this->migratorConfig->setAllOrNothing(true);

        self::assertTrue($this->migratorConfig->isAllOrNothing());
    }

    protected function setUp() : void
    {
        $this->migratorConfig = new MigratorConfig();
    }
}
