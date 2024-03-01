<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Event;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Event\MigrationsEventArgs;
use Doctrine\Migrations\Event\MigrationsVersionEventArgs;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\Version\Direction;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventArgsTest extends TestCase
{
    /** @var Connection&MockObject */
    private Connection $connection;

    /** @var MigratorConfiguration&MockObject */
    private MigratorConfiguration $config;

    private MigrationPlan $plan;

    public function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->config     = $this->createMock(MigratorConfiguration::class);
        $migration        = $this->createMock(AbstractMigration::class);
        $this->plan       = new MigrationPlan(new Version('1'), $migration, Direction::UP);
    }

    public function testMigrationsVersionEventArgs(): void
    {
        $event = new MigrationsVersionEventArgs($this->connection, $this->plan, $this->config);

        self::assertSame($this->connection, $event->getConnection());
        self::assertSame($this->config, $event->getMigratorConfiguration());
        self::assertSame($this->plan, $event->getPlan());
    }

    public function testMigrationsEventArgs(): void
    {
        $plan  = new MigrationPlanList([], Direction::UP);
        $event = new MigrationsEventArgs($this->connection, $plan, $this->config);

        self::assertSame($this->connection, $event->getConnection());
        self::assertSame($this->config, $event->getMigratorConfiguration());
        self::assertSame($plan, $event->getPlan());
    }
}
