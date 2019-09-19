<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\ParameterIncompatibleWithFinder;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\MigrationDeepFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Generator\DiffGenerator;
use Doctrine\Migrations\Generator\FileBuilder;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Generator\SqlGenerator;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\MetadataStorageConfigration;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\Migrations\Provider\OrmSchemaProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\Migrations\Provider\SchemaProviderInterface;
use Doctrine\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Doctrine\Migrations\Tools\Console\MigratorConfigurationFactory;
use Doctrine\Migrations\Tools\Console\MigratorConfigurationFactoryInterface;
use Doctrine\Migrations\Version\AliasResolver;
use Doctrine\Migrations\Version\Executor;
use Doctrine\Migrations\Version\Factory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;

/**
 * The DepenencyFactory is responsible for wiring up and managing internal class dependencies.
 *
 * @internal
 */
class DependencyFactory
{
    /** @var Configuration */
    private $configuration;

    /** @var object[] */
    private $dependencies = [];

    /** @var LoggerInterface */
    private $logger;

    /** @var Connection */
    private $connection;

    /** @var callable */
    private $sorter;

    /**
     * @var EntityManagerInterface|null
     */
    private $em;

    public function __construct(Configuration $configuration, Connection $connection, ?EntityManagerInterface $em = null, ?LoggerInterface $logger = null)
    {
        $this->configuration = $configuration;
        $this->logger        = $logger ?: new NullLogger();
        $this->connection    = $connection;
        $this->em = $em;
    }


    public function getConfiguration() : Configuration
    {
        return $this->configuration;
    }

    public function getConnection() : Connection
    {
        return $this->connection;
    }

    public function getEventDispatcher() : EventDispatcher
    {
        return $this->getDependency(EventDispatcher::class, function () : EventDispatcher {
            return new EventDispatcher(
                $this->connection,
                $this->getConfiguration(),
                $this->connection->getEventManager()
            );
        });
    }

    public function getSchemaDumper() : SchemaDumper
    {
        return $this->getDependency(SchemaDumper::class, function () : SchemaDumper {
            return new SchemaDumper(
                $this->connection->getDatabasePlatform(),
                $this->connection->getSchemaManager(),
                $this->getMigrationGenerator(),
                $this->getMigrationSqlGenerator()
            );
        });
    }

    private function getSchemaProvider(): SchemaProviderInterface
    {
        return $this->getDependency(SchemaProviderInterface::class, function () : SchemaProviderInterface {
            return new OrmSchemaProvider($this->em);
        });
    }

    public function getDiffGenerator() : DiffGenerator
    {
        return $this->getDependency(DiffGenerator::class, function () : DiffGenerator {
            return new DiffGenerator(
                $this->getConnection()->getConfiguration(),
                $this->getConnection()->getSchemaManager(),
                $this->getSchemaProvider(),
                $this->getConnection()->getDatabasePlatform(),
                $this->getMigrationGenerator(),
                $this->getMigrationSqlGenerator()
            );
        });
    }
    public function getSchemaDiffProvider() : SchemaDiffProviderInterface
    {
        return $this->getDependency(SchemaDiffProviderInterface::class, function () : LazySchemaDiffProvider {
            return LazySchemaDiffProvider::fromDefaultProxyFactoryConfiguration(
                new SchemaDiffProvider(
                    $this->connection->getSchemaManager(),
                    $this->connection->getDatabasePlatform()
                )
            );
        });
    }

    public function getFileBuilder() : FileBuilder
    {
        return $this->getDependency(FileBuilder::class, function () : FileBuilder {
            return new FileBuilder();
        });
    }

    public function getParameterFormatter() : ParameterFormatterInterface
    {
        return $this->getDependency(ParameterFormatter::class, function () : ParameterFormatter {
            return new ParameterFormatter($this->connection);
        });
    }

    public function getMigrationsFinder() : MigrationFinder
    {
        $finder =  $this->getDependency(GlobFinder::class, static function () : MigrationFinder {
            return new GlobFinder();
        });

        // todo move this to DI

        if (! ($finder instanceof MigrationDeepFinder) && ($this->getConfiguration()->areMigrationsOrganizedByYear() || $this->getConfiguration()->areMigrationsOrganizedByYearAndMonth())) {
            throw ParameterIncompatibleWithFinder::new(
                'organize-migrations',
                $finder
            );
        }

        return $finder;
    }

    public function setEntityManager(EntityManagerInterface $em) : void
    {
        $this->em = $em;
    }

    public function setSorter(callable $sorter) : void
    {
        $this->sorter = $sorter;
    }

    public function getMigrationRepository() : MigrationRepository
    {
        return $this->getDependency(MigrationRepository::class, function () : MigrationRepository {
            return new MigrationRepository(
                $this->getConfiguration()->getMigrationDirectories(),
                $this->getMigrationsFinder(),
                new Factory($this->getConnection(), $this->getVersionExecutor(), $this->getLogger()),
                $this->sorter
            );
        });
    }

    public function setMetadataStorageConfiguration(MetadataStorageConfigration $metadataStorageConfigration)
    {
        $this->dependencies[MetadataStorageConfigration::class] = $metadataStorageConfigration;
    }

    private function getMetadataStorageConfiguration() : MetadataStorageConfigration
    {
        return $this->getDependency(MetadataStorageConfigration::class, function () : MetadataStorageConfigration {
            return new TableMetadataStorageConfiguration();
        });
    }

    public function getMetadataStorage() : MetadataStorage
    {
        return $this->getDependency(TableMetadataStorage::class, function () : MetadataStorage {
            return new TableMetadataStorage(
                $this->connection,
                $this->getMetadataStorageConfiguration()
            );
        });
    }

    public function getEntityManager() : ?EntityManagerInterface
    {
        return $this->em;
    }

    public function getLogger() : LoggerInterface
    {
        return $this->logger;
    }

    public function getVersionExecutor() : Executor
    {
        return $this->getDependency(Executor::class, function () : Executor {
            return new Executor(
                $this->getMetadataStorage(),
                $this->getEventDispatcher(),
                $this->connection,
                $this->getSchemaDiffProvider(),
                $this->getLogger(),
                $this->getParameterFormatter(),
                $this->getStopwatch()
            );
        });
    }

    public function getQueryWriter() : QueryWriter
    {
        return $this->getDependency(QueryWriter::class, function () : QueryWriter {
            return new FileQueryWriter(
                $this->getFileBuilder(),
                $this->logger
            );
        });
    }

    public function getVersionAliasResolver() : AliasResolver
    {
        return $this->getDependency(AliasResolver::class, function () : AliasResolver {
            return new AliasResolver(
                $this->getMigrationRepository(),
                $this->getMetadataStorage()
            );
        });
    }

    public function getMigrationPlanCalculator() : MigrationPlanCalculator
    {
        return $this->getDependency(MigrationPlanCalculator::class, function () : MigrationPlanCalculator {
            return new MigrationPlanCalculator(
                $this->getMigrationRepository(),
                $this->getMetadataStorage()
            );
        });
    }

    public function getRecursiveRegexFinder() : RecursiveRegexFinder
    {
        return $this->getDependency(RecursiveRegexFinder::class, static function () : RecursiveRegexFinder {
            return new RecursiveRegexFinder();
        });
    }

    public function getMigrationGenerator() : Generator
    {
        return $this->getDependency(Generator::class, function () : Generator {
            return new Generator($this->getConfiguration());
        });
    }

    public function getMigrationSqlGenerator() : SqlGenerator
    {
        return $this->getDependency(SqlGenerator::class, function () : SqlGenerator {
            return new SqlGenerator(
                $this->getConfiguration(),
                $this->connection->getDatabasePlatform(),
                $this->getMetadataStorageConfiguration()
            );
        });
    }

    public function getMigratorConfigurationFactory() : MigratorConfigurationFactoryInterface
    {
        return $this->getDependency(MigratorConfigurationFactoryInterface::class, function () : MigratorConfigurationFactoryInterface {
            return new MigratorConfigurationFactory(
                $this->getConfiguration()
            );
        });
    }

    public function getMigrationStatusInfosHelper() : MigrationStatusInfosHelper
    {
        return $this->getDependency(MigrationStatusInfosHelper::class, function () : MigrationStatusInfosHelper {
            return new MigrationStatusInfosHelper(
                $this->getConfiguration(),
                $this->connection,
                $this->getVersionAliasResolver()
            );
        });
    }

    public function getMigrator() : MigratorInterface
    {
        return $this->getDependency(MigratorInterface::class, function () : MigratorInterface {
            return new Migrator(
                $this->connection,
                $this->getEventDispatcher(),
                $this->getVersionExecutor(),
                $this->logger,
                $this->getStopwatch()
            );
        });
    }

    public function getStopwatch() : Stopwatch
    {
        return $this->getDependency(Stopwatch::class, static function () : Stopwatch {
            $symfonyStopwatch = new SymfonyStopwatch(true);

            return new Stopwatch($symfonyStopwatch);
        });
    }

    public function getRollup() : Rollup
    {
        return $this->getDependency(Rollup::class, function () : Rollup {
            return new Rollup(
                $this->getMetadataStorage(),
                $this->getMigrationRepository()
            );
        });
    }

    /**
     * @return mixed
     */
    private function getDependency(string $className, callable $callback)
    {
        if (! isset($this->dependencies[$className])) {
            $this->dependencies[$className] = $callback();
        }

        return $this->dependencies[$className];
    }
}
