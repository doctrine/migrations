<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Exception\FrozenDependencies;
use Doctrine\Migrations\Exception\MissingDependency;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Provider\OrmSchemaProvider;
use Doctrine\Migrations\Provider\SchemaProvider;
use Doctrine\Migrations\Tests\MigrationRepository\Migrations\A\A;
use Doctrine\Migrations\Tests\Stub\CustomClassNameMigrationFactory;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use stdClass;

final class DependencyFactoryTest extends MigrationTestCase
{
    /** @var Connection&MockObject */
    private Connection $connection;

    private Configuration $configuration;

    /** @var EntityManager&MockObject */
    private EntityManager $entityManager;

    public function setUp(): void
    {
        $this->connection    = $this->createMock(Connection::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->entityManager
            ->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->configuration = new Configuration();
    }

    public function testFreeze(): void
    {
        $this->configuration->addMigrationsDirectory('foo', 'bar');

        $di = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $di->freeze();

        $this->expectException(FrozenDependencies::class);
        $this->expectExceptionMessage('The dependencies are frozen and cannot be edited anymore.');
        $di->setService('foo', new stdClass());
    }

    public function testFreezeForDefinition(): void
    {
        $this->configuration->addMigrationsDirectory('foo', 'bar');

        $di = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $di->freeze();

        $this->expectException(FrozenDependencies::class);
        $this->expectExceptionMessage('The dependencies are frozen and cannot be edited anymore.');
        $di->setDefinition('foo', static function (): void {
        });
    }

    public function testFinderForYearMonthStructure(): void
    {
        $this->configuration->setMigrationsAreOrganizedByYearAndMonth(true);

        $di     = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $finder = $di->getMigrationsFinder();

        self::assertInstanceOf(RecursiveRegexFinder::class, $finder);
    }

    public function testFinderForYearStructure(): void
    {
        $this->configuration->setMigrationsAreOrganizedByYear(true);

        $di     = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $finder = $di->getMigrationsFinder();

        self::assertInstanceOf(RecursiveRegexFinder::class, $finder);
    }

    public function testFinder(): void
    {
        $this->configuration = new Configuration();
        $di                  = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $finder              = $di->getMigrationsFinder();

        self::assertInstanceOf(GlobFinder::class, $finder);
    }

    public function testConnection(): void
    {
        $di = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));

        self::assertSame($this->connection, $di->getConnection());
        self::assertFalse($di->hasEntityManager());
        self::assertTrue($di->isFrozen());
    }

    public function testNoEntityManagerRaiseException(): void
    {
        $this->expectException(MissingDependency::class);

        $di = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $di->getEntityManager();
    }

    public function testEntityManager(): void
    {
        $di = DependencyFactory::fromEntityManager(new ExistingConfiguration($this->configuration), new ExistingEntityManager($this->entityManager));

        self::assertTrue($di->hasEntityManager());
        self::assertSame($this->entityManager, $di->getEntityManager());
        self::assertSame($this->connection, $di->getConnection());
        self::assertTrue($di->isFrozen());
    }

    public function testNoSchemaProviderRaiseException(): void
    {
        $di = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        self::assertFalse($di->hasSchemaProvider());

        $this->expectException(MissingDependency::class);
        $di->getSchemaProvider();
    }

    public function testSchemaProviderFromEntityManager(): void
    {
        $di = DependencyFactory::fromEntityManager(new ExistingConfiguration($this->configuration), new ExistingEntityManager($this->entityManager));

        self::assertTrue($di->hasSchemaProvider());
        self::assertEquals(new OrmSchemaProvider($this->entityManager), $di->getSchemaProvider());
        self::assertTrue($di->isFrozen());
    }

    public function testSchemaProviderFromManualService(): void
    {
        $schemaProvider = $this->createMock(SchemaProvider::class);
        $di             = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));
        $di->setService(SchemaProvider::class, $schemaProvider);

        self::assertTrue($di->hasSchemaProvider());
        self::assertSame($schemaProvider, $di->getSchemaProvider());
        self::assertFalse($di->isFrozen());
    }

    public function testProvidedSchemaProviderHasPrecedenceOverProviderFromEntityManagerConfig(): void
    {
        $schemaProvider = $this->createMock(SchemaProvider::class);
        $di             = DependencyFactory::fromEntityManager(new ExistingConfiguration($this->configuration), new ExistingEntityManager($this->entityManager));
        $di->setService(SchemaProvider::class, $schemaProvider);

        self::assertTrue($di->hasSchemaProvider());
        self::assertSame($schemaProvider, $di->getSchemaProvider());
        self::assertFalse($di->isFrozen());
    }

    public function testCustomLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $di     = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection), $logger);
        self::assertSame($logger, $di->getLogger());
    }

    public function testOverrideCustomLogger(): void
    {
        $logger        = $this->createMock(LoggerInterface::class);
        $anotherLogger = $this->createMock(LoggerInterface::class);
        $di            = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection), $logger);
        $di->setService(LoggerInterface::class, $anotherLogger);
        self::assertSame($anotherLogger, $di->getLogger());
        self::assertFalse($di->isFrozen());
    }

    public function testServiceDefinition(): void
    {
        $logger        = $this->createMock(LoggerInterface::class);
        $anotherLogger = $this->createMock(LoggerInterface::class);
        $di            = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection), $logger);

        $di->setDefinition(LoggerInterface::class, static function (DependencyFactory $innerDi) use ($anotherLogger, $di) {
            self::assertSame($di, $innerDi);

            return $anotherLogger;
        });
        self::assertSame($anotherLogger, $di->getLogger());
    }

    public function testServiceDecoratesDefaultImplementation(): void
    {
        $configuration = new Configuration();
        $configuration->addMigrationClass(A::class);

        $di = DependencyFactory::fromConnection(new ExistingConfiguration($configuration), new ExistingConnection($this->connection));

        $di->setDefinition(MigrationFactory::class, static function (DependencyFactory $innerDi): CustomClassNameMigrationFactory {
            $serviceToDecorate = $innerDi->getMigrationFactory();

            return new CustomClassNameMigrationFactory($serviceToDecorate, A::class);
        });

        $factory = $di->getMigrationFactory();
        self::assertInstanceOf(A::class, $factory->createVersion('SomeVersion'));
    }

    public function testServiceHasPriorityOverDefinition(): void
    {
        $logger        = $this->createMock(LoggerInterface::class);
        $anotherLogger = $this->createMock(LoggerInterface::class);
        $di            = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));

        $di->setDefinition(LoggerInterface::class, static function (DependencyFactory $innerDi) use ($anotherLogger, $di) {
            self::assertSame($di, $innerDi);

            return $anotherLogger;
        });
        $di->setService(LoggerInterface::class, $logger);
        self::assertSame($logger, $di->getLogger());
    }

    public function testChangingConfigurationsDoesNotFreezeTheFactory(): void
    {
        $di = DependencyFactory::fromConnection(new ExistingConfiguration($this->configuration), new ExistingConnection($this->connection));

        $newConfiguration = new Configuration();
        $di->setConfigurationLoader(new ExistingConfiguration($newConfiguration));
        self::assertFalse($di->isFrozen());

        self::assertSame($newConfiguration, $di->getConfiguration());
        self::assertTrue($di->isFrozen());
    }

    public function testMetadataConfigurationIsPassedToTableStorage(): void
    {
        $connection     = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $metadataConfig = new TableMetadataStorageConfiguration();
        $metadataConfig->setTableName('foo');
        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration($metadataConfig);

        $di = DependencyFactory::fromConnection(new ExistingConfiguration($configuration), new ExistingConnection($connection));
        $di->getMetadataStorage()->ensureInitialized();

        self::assertTrue($connection->createSchemaManager()->tablesExist(['foo']));
        self::assertTrue($di->isFrozen());
    }
}
