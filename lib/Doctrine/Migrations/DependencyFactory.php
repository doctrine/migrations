<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MissingDependency;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Generator\ClassNameGenerator;
use Doctrine\Migrations\Generator\ConcatenationFileBuilder;
use Doctrine\Migrations\Generator\DiffGenerator;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Generator\SqlGenerator;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\MetadataStorageConfigration;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Provider\DBALSchemaDiffProvider;
use Doctrine\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\Migrations\Provider\OrmSchemaProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaProvider;
use Doctrine\Migrations\Tools\Console\ConsoleInputMigratorConfigurationFactory;
use Doctrine\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Doctrine\Migrations\Tools\Console\MigratorConfigurationFactory;
use Doctrine\Migrations\Version\DefaultAliasResolver;
use Doctrine\Migrations\Version\AliasResolver;
use Doctrine\Migrations\Version\DbalExecutor;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;
use function preg_quote;
use function sprintf;

/**
 * The DependencyFactory is responsible for wiring up and managing internal class dependencies.
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

    /** @var EntityManagerInterface|null */
    private $em;

    public function __construct(Configuration $configuration, Connection $connection, ?EntityManagerInterface $em = null, ?LoggerInterface $logger = null)
    {
        $this->configuration = $configuration;
        $this->logger        = $logger ?: new NullLogger();
        $this->connection    = $connection;
        $this->em            = $em;
    }

    public function getConfiguration() : Configuration
    {
        return $this->configuration;
    }

    private function getConnection() : Connection
    {
        return $this->connection;
    }

    public function getEventDispatcher() : EventDispatcher
    {
        return $this->getDependency(EventDispatcher::class, function () : EventDispatcher {
            return new EventDispatcher(
                $this->getConnection(),
                $this->getConnection()->getEventManager()
            );
        });
    }

    public function getClassNameGenerator() : ClassNameGenerator
    {
        return $this->getDependency(ClassNameGenerator::class, static function () : ClassNameGenerator {
            return new ClassNameGenerator();
        });
    }

    public function getSchemaDumper() : SchemaDumper
    {
        return $this->getDependency(SchemaDumper::class, function () : SchemaDumper {
            $excludedTables = [];

            $metadataConfig = $this->configuration->getMetadataStorageConfiguration();
            if ($metadataConfig instanceof TableMetadataStorageConfiguration) {
                $excludedTables[] = sprintf('/^%s$/', preg_quote($metadataConfig->getTableName(), '/'));
            }

            return new SchemaDumper(
                $this->getConnection()->getDatabasePlatform(),
                $this->getConnection()->getSchemaManager(),
                $this->getMigrationGenerator(),
                $this->getMigrationSqlGenerator(),
                $excludedTables
            );
        });
    }

    private function getSchemaProvider() : SchemaProvider
    {
        return $this->getDependency(SchemaProvider::class, function () : SchemaProvider {
            if ($this->em === null) {
                throw new MissingDependency('The doctrine entity manager should be provided in order to be able to instantiate SchemaProvider');
            }

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

    public function getSchemaDiffProvider() : SchemaDiffProvider
    {
        return $this->getDependency(SchemaDiffProvider::class, function () : LazySchemaDiffProvider {
            return LazySchemaDiffProvider::fromDefaultProxyFactoryConfiguration(
                new DBALSchemaDiffProvider(
                    $this->connection->getSchemaManager(),
                    $this->connection->getDatabasePlatform()
                )
            );
        });
    }

    public function getFileBuilder() : ConcatenationFileBuilder
    {
        return $this->getDependency(ConcatenationFileBuilder::class, static function () : ConcatenationFileBuilder {
            return new ConcatenationFileBuilder();
        });
    }

    public function getParameterFormatter() : ParameterFormatter
    {
        return $this->getDependency(InlineParameterFormatter::class, function () : InlineParameterFormatter {
            return new InlineParameterFormatter($this->connection);
        });
    }

    public function getMigrationsFinder() : MigrationFinder
    {
        return $this->getDependency(GlobFinder::class, function () : MigrationFinder {
            $configs              = $this->getConfiguration();
            $needsRecursiveFinder = $configs->areMigrationsOrganizedByYear() || $configs->areMigrationsOrganizedByYearAndMonth();

            return $needsRecursiveFinder ? new RecursiveRegexFinder() : new GlobFinder();
        });
    }

    public function setSorter(callable $sorter) : void
    {
        $this->sorter = $sorter;
    }

    public function getMigrationRepository() : MigrationRepository
    {
        return $this->getDependency(MigrationRepository::class, function () : MigrationRepository {
            return new MigrationRepository(
                $this->getConfiguration()->getMigrationClasses(),
                $this->getConfiguration()->getMigrationDirectories(),
                $this->getMigrationsFinder(),
                new MigrationFactory($this->getConnection(), $this->getLogger()),
                $this->sorter
            );
        });
    }

    public function setMetadataStorageConfiguration(MetadataStorageConfigration $metadataStorageConfigration) : void
    {
        $this->dependencies[MetadataStorageConfigration::class] = $metadataStorageConfigration;
    }

    private function getMetadataStorageConfiguration() : MetadataStorageConfigration
    {
        return $this->getDependency(MetadataStorageConfigration::class, static function () : MetadataStorageConfigration {
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

    public function getVersionExecutor() : DbalExecutor
    {
        return $this->getDependency(DbalExecutor::class, function () : DbalExecutor {
            return new DbalExecutor(
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
            return new DefaultAliasResolver(
                $this->getMigrationRepository(),
                $this->getMetadataStorage(),
                $this->getMigrationPlanCalculator()
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
                $this->connection->getDatabasePlatform()
            );
        });
    }

    public function getConsoleInputMigratorConfigurationFactory() : MigratorConfigurationFactory
    {
        return $this->getDependency(MigratorConfigurationFactory::class, function () : MigratorConfigurationFactory {
            return new ConsoleInputMigratorConfigurationFactory(
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

    public function getMigrator() : Migrator
    {
        return $this->getDependency(Migrator::class, function () : Migrator {
            return new DbalMigrator(
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
