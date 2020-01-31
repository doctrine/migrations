<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\FrozenDependencies;
use Doctrine\Migrations\Exception\MissingDependency;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\Version;
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

    public function testTwoLevelDecorator() : void
    {
        $this->configuration->addMigrationsDirectory('foo', 'bar');

        $di = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));

        $di->decorateService(Comparator::class, function (DependencyFactory $dependencyFactory) : Comparator {
            $oldComparator = $dependencyFactory->getVersionComparator();

            return new class($oldComparator) implements Comparator
            {
                /** @var Comparator */
                private $comparator;

                public function __construct(Comparator $comparator)
                {
                    $this->comparator = $comparator;
                }

                public function compare(Version $a, Version $b) : int
                {
                    return $this->comparator->compare($a, $b) * 10;
                }
            };
        });

        $di->decorateService(Comparator::class, function (DependencyFactory $dependencyFactory) : Comparator {
            $oldComparator = $dependencyFactory->getVersionComparator();

            return new class($oldComparator) implements Comparator
            {
                /** @var Comparator */
                private $comparator;

                public function __construct(Comparator $comparator)
                {
                    $this->comparator = $comparator;
                }

                public function compare(Version $a, Version $b) : int
                {
                    return $this->comparator->compare($a, $b) * -10;
                }
            };
        });

        $comparator = $di->getVersionComparator();

        self::assertSame(100, $comparator->compare(new Version('1'), new Version('2')));
    }

    public function testFreeze() : void
    {
        $this->configuration->addMigrationsDirectory('foo', 'bar');

        $di = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $di->freeze();

        $this->expectException(FrozenDependencies::class);
        $this->expectExceptionMessage('The dependencies are frozen and cannot be edited anymore.');
        $di->setService('foo', new stdClass());
    }

    public function testFinderForYearMonthStructure() : void
    {
        $this->configuration->setMigrationsAreOrganizedByYearAndMonth(true);

        $di     = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $finder = $di->getMigrationsFinder();

        self::assertInstanceOf(RecursiveRegexFinder::class, $finder);
    }

    public function testFinderForYearStructure() : void
    {
        $this->configuration->setMigrationsAreOrganizedByYear(true);

        $di     = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $finder = $di->getMigrationsFinder();

        self::assertInstanceOf(RecursiveRegexFinder::class, $finder);
    }

    public function testFinder() : void
    {
        $this->configuration = new Configuration();
        $di                  = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $finder              = $di->getMigrationsFinder();

        self::assertInstanceOf(GlobFinder::class, $finder);
    }

    public function testConnection() : void
    {
        $di = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));

        self::assertSame($this->connection, $di->getConnection());
        self::assertFalse($di->hasEntityManager());
    }

    public function testNoEntityManagerRaiseException() : void
    {
        $this->expectException(MissingDependency::class);

        $di = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $di->getEntityManager();
    }

    public function testEntityManager() : void
    {
        $di = DependencyFactory::fromEntityManager(new ExistingConfiguration($this->configuration), new ExistingEntityManager($this->entityManager));

        self::assertTrue($di->hasEntityManager());
        self::assertSame($this->entityManager, $di->getEntityManager());
        self::assertSame($this->connection, $di->getConnection());
    }
}
