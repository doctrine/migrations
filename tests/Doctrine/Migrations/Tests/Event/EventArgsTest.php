<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Event;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Event\MigrationsEventArgs;
use Doctrine\Migrations\Event\MigrationsVersionEventArgs;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\MigratorConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventArgsTest extends TestCase
{
    /** @var MockObject|Connection */
    private $connection;

    /** @var MockObject|MigratorConfiguration */
    private $config;

    public function setUp() : void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->config     = $this->createMock(MigratorConfiguration::class);
    }

    public function testMigrationsVersionEventArgs() : void
    {
        $plan  = $this->createMock(MigrationPlan::class);
        $event = new MigrationsVersionEventArgs($this->connection, $plan, $this->config);

        self::assertSame($this->connection, $event->getConnection());
        self::assertSame($this->config, $event->getMigratorConfiguration());
        self::assertSame($plan, $event->getPlan());
    }

    public function testMigrationsEventArgs() : void
    {
        $plan  = $this->createMock(MigrationPlanList::class);
        $event = new MigrationsEventArgs($this->connection, $plan, $this->config);

        self::assertSame($this->connection, $event->getConnection());
        self::assertSame($this->config, $event->getMigratorConfiguration());
        self::assertSame($plan, $event->getPlan());
    }
}
