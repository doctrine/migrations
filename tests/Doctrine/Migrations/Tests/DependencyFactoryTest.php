<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\FrozenDependencies;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

final class DependencyFactoryTest extends MigrationTestCase
{
    /** @var MockObject|Connection */
    private $connection;

    /** @var Configuration */
    private $configuration;

    /** @var EntityManager|MockObject */
    private $entityManager;

    public function setUp() : void
    {
        $this->connection    = $this->createMock(Connection::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager
            ->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->configuration = new Configuration();
    }

    public function testFreeze() : void
    {
        $this->configuration->addMigrationsDirectory('foo', 'bar');

        $di = DependencyFactory::fromConnection($this->configuration, $this->connection);
        $di->freeze();

        $this->expectException(FrozenDependencies::class);
        $this->expectExceptionMessage('The dependencies are frozen and cannot be edited anymore.');
        $di->setService('foo', new stdClass());
    }

    public function testFinderForYearMonthStructure() : void
    {
        $this->configuration->setMigrationsAreOrganizedByYearAndMonth(true);

        $di     = DependencyFactory::fromConnection($this->configuration, $this->connection);
        $finder = $di->getMigrationsFinder();

        self::assertInstanceOf(RecursiveRegexFinder::class, $finder);
    }

    public function testFinderForYearStructure() : void
    {
        $this->configuration->setMigrationsAreOrganizedByYear(true);

        $di     = DependencyFactory::fromConnection($this->configuration, $this->connection);
        $finder = $di->getMigrationsFinder();

        self::assertInstanceOf(RecursiveRegexFinder::class, $finder);
    }

    public function testFinder() : void
    {
        $this->configuration = new Configuration();
        $di                  = DependencyFactory::fromConnection($this->configuration, $this->connection);
        $finder              = $di->getMigrationsFinder();

        self::assertInstanceOf(GlobFinder::class, $finder);
    }

    public function testConnection() : void
    {
        $di = DependencyFactory::fromConnection($this->configuration, $this->connection);

        self::assertSame($this->connection, $di->getConnection());
        self::assertNull($di->getEntityManager());
    }

    public function testEntityManager() : void
    {
        $di = DependencyFactory::fromEntityManager($this->configuration, $this->entityManager);

        self::assertSame($this->entityManager, $di->getEntityManager());
        self::assertSame($this->connection, $di->getConnection());
    }
}
