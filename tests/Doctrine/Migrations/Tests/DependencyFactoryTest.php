<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use PHPUnit\Framework\MockObject\MockObject;

final class DependencyFactoryTest extends MigrationTestCase
{
    /** @var MockObject|Connection */
    private $connection;

    public function setUp() : void
    {
        $this->connection = $this->createMock(Connection::class);
    }

    public function testFinderForYearMonthStructure() : void
    {
        $conf = new Configuration();
        $conf->setMigrationsAreOrganizedByYearAndMonth(true);

        $di     = new DependencyFactory($conf, $this->connection);
        $finder = $di->getMigrationsFinder();

        self::assertInstanceOf(RecursiveRegexFinder::class, $finder);
    }

    public function testFinderForYearStructure() : void
    {
        $conf = new Configuration();
        $conf->setMigrationsAreOrganizedByYear(true);

        $di     = new DependencyFactory($conf, $this->connection);
        $finder = $di->getMigrationsFinder();

        self::assertInstanceOf(RecursiveRegexFinder::class, $finder);
    }

    public function testFinder() : void
    {
        $conf   = new Configuration();
        $di     = new DependencyFactory($conf, $this->connection);
        $finder = $di->getMigrationsFinder();

        self::assertInstanceOf(GlobFinder::class, $finder);
    }
}
