<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ConnectionLoader;
use Doctrine\Migrations\Configuration\EntityManager\EntityManagerLoader;
use Doctrine\Migrations\Exception\FrozenDependencies;
use Doctrine\Migrations\Exception\MissingDependency;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Generator\ClassNameGenerator;
use Doctrine\Migrations\Generator\ConcatenationFileBuilder;
use Doctrine\Migrations\Generator\DiffGenerator;
use Doctrine\Migrations\Generator\FileBuilder;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Generator\SqlGenerator;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\Metadata\Storage\MetadataStorageConfiguration;
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
use Doctrine\Migrations\Version\AliasResolver;
use Doctrine\Migrations\Version\CurrentMigrationStatusCalculator;
use Doctrine\Migrations\Version\DbalExecutor;
use Doctrine\Migrations\Version\DefaultAliasResolver;
use Doctrine\Migrations\Version\Executor;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\MigrationPlanCalculator;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Doctrine\Migrations\Version\SortedMigrationPlanCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;
use function array_key_exists;
use function preg_quote;
use function sprintf;

/**
 * The DependencyFactory is responsible for wiring up and managing internal class dependencies.
 */
class DependencyFactory
{
    public const MIGRATIONS_SORTER = 'Doctrine\Migrations\MigrationsSorter';

    /** @var Configuration */
    private $configuration;

    /** @var object[]|callable[] */
    private $dependencies = [];

    /** @var LoggerInterface */
    private $logger;

    /** @var Connection */
    private $connection;

    /** @var EntityManagerInterface|null */
    private $em;

    /** @var bool */
    private $frozen = false;

    /** @var Configuration\ConfigurationLoader */
    private $configurationLoader;

    /** @var ConnectionLoader */
    private $connectionLoader;

    /** @var EntityManagerLoader|null */
    private $emLoader;

    public static function fromConnection(
        Configuration\ConfigurationLoader $configurationLoader,
        ConnectionLoader $connectionLoader,
        ?LoggerInterface $logger = null
    ) : self {
        $dependencyFactory                      = new self($logger);
        $dependencyFactory->configurationLoader = $configurationLoader;
        $dependencyFactory->connectionLoader    = $connectionLoader;

        return $dependencyFactory;
    }

    public static function fromEntityManager(
        Configuration\ConfigurationLoader $configurationLoader,
        EntityManagerLoader $emLoader,
        ?LoggerInterface $logger = null
    ) : self {
        $dependencyFactory                      = new self($logger);
        $dependencyFactory->configurationLoader = $configurationLoader;
        $dependencyFactory->emLoader            = $emLoader;

        return $dependencyFactory;
    }

    private function __construct(?LoggerInterface $logger)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    public function isFrozen() : bool
    {
        return $this->frozen;
    }

    public function freeze() : void
    {
        $this->frozen = true;
        $this->getConfiguration()->freeze();
    }

    private function assertNotFrozen() : void
    {
        if ($this->frozen) {
            throw FrozenDependencies::new();
        }
    }

    public function hasEntityManager() : bool
    {
        return $this->emLoader !== null;
    }

    public function getConfiguration() : Configuration
    {
        if ($this->configuration === null) {
            $this->configuration = $this->configurationLoader->getConfiguration();
        }

        return $this->configuration;
    }

    public function getConnection() : Connection
    {
        if ($this->connection === null) {
            $this->connection = $this->hasEntityManager()
                ? $this->getEntityManager()->getConnection()
                : $this->connectionLoader->getConnection();
        }

        return $this->connection;
    }

    public function getEntityManager() : EntityManagerInterface
    {
        if ($this->em === null) {
            if ($this->emLoader === null) {
                throw MissingDependency::noEntityManager();
            }
            $this->em = $this->emLoader->getEntityManager();
        }

        return $this->em;
    }

    public function getLogger() : LoggerInterface
    {
        return $this->logger;
    }

    public function getSorter() : ?callable
    {
        return $this->getDependency(self::MIGRATIONS_SORTER, static function () {
            return null;
        });
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

            $metadataConfig = $this->getConfiguration()->getMetadataStorageConfiguration();
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
            return new OrmSchemaProvider($this->getEntityManager());
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
                    $this->getConnection()->getSchemaManager(),
                    $this->getConnection()->getDatabasePlatform()
                )
            );
        });
    }

    public function getFileBuilder() : FileBuilder
    {
        return $this->getDependency(FileBuilder::class, static function () : FileBuilder {
            return new ConcatenationFileBuilder();
        });
    }

    public function getParameterFormatter() : ParameterFormatter
    {
        return $this->getDependency(ParameterFormatter::class, function () : ParameterFormatter {
            return new InlineParameterFormatter($this->getConnection());
        });
    }

    public function getMigrationsFinder() : MigrationFinder
    {
        return $this->getDependency(MigrationFinder::class, function () : MigrationFinder {
            $configs              = $this->getConfiguration();
            $needsRecursiveFinder = $configs->areMigrationsOrganizedByYear() || $configs->areMigrationsOrganizedByYearAndMonth();

            return $needsRecursiveFinder ? new RecursiveRegexFinder() : new GlobFinder();
        });
    }

    public function getMigrationRepository() : MigrationRepository
    {
        return $this->getDependency(MigrationRepository::class, function () : MigrationRepository {
            return new MigrationRepository(
                $this->getConfiguration()->getMigrationClasses(),
                $this->getConfiguration()->getMigrationDirectories(),
                $this->getMigrationsFinder(),
                new MigrationFactory($this->getConnection(), $this->getLogger()),
                $this->getSorter()
            );
        });
    }

    /**
     * @param object|callable $service
     */
    public function setService(string $id, $service) : void
    {
        $this->assertNotFrozen();
        $this->dependencies[$id] = $service;
    }

    private function getMetadataStorageConfiguration() : MetadataStorageConfiguration
    {
        return $this->getDependency(MetadataStorageConfiguration::class, static function () : MetadataStorageConfiguration {
            return new TableMetadataStorageConfiguration();
        });
    }

    public function getMetadataStorage() : MetadataStorage
    {
        return $this->getDependency(MetadataStorage::class, function () : MetadataStorage {
            return new TableMetadataStorage(
                $this->getConnection(),
                $this->getMetadataStorageConfiguration()
            );
        });
    }

    public function getVersionExecutor() : Executor
    {
        return $this->getDependency(Executor::class, function () : Executor {
            return new DbalExecutor(
                $this->getMetadataStorage(),
                $this->getEventDispatcher(),
                $this->getConnection(),
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
                $this->getMigrationStatusCalculator()
            );
        });
    }

    public function getMigrationStatusCalculator() : MigrationStatusCalculator
    {
        return $this->getDependency(MigrationStatusCalculator::class, function () : MigrationStatusCalculator {
            return new CurrentMigrationStatusCalculator(
                $this->getMigrationRepository(),
                $this->getMetadataStorage()
            );
        });
    }

    public function getMigrationPlanCalculator() : MigrationPlanCalculator
    {
        return $this->getDependency(MigrationPlanCalculator::class, function () : MigrationPlanCalculator {
            return new SortedMigrationPlanCalculator(
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
                $this->getConnection()->getDatabasePlatform()
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
                $this->getConnection(),
                $this->getVersionAliasResolver()
            );
        });
    }

    public function getMigrator() : Migrator
    {
        return $this->getDependency(Migrator::class, function () : Migrator {
            return new DbalMigrator(
                $this->getConnection(),
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
    private function getDependency(string $id, callable $callback)
    {
        if (! array_key_exists($id, $this->dependencies)) {
            $this->dependencies[$id] = $callback();
        }

        return $this->dependencies[$id];
    }
}
