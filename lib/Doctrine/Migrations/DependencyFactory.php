<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ConnectionLoader;
use Doctrine\Migrations\Configuration\EntityManager\EntityManagerLoader;
use Doctrine\Migrations\Configuration\Migration\ConfigurationLoader;
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
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorage;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\Migrations\Provider\DBALSchemaDiffProvider;
use Doctrine\Migrations\Provider\EmptySchemaProvider;
use Doctrine\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\Migrations\Provider\OrmSchemaProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaProvider;
use Doctrine\Migrations\Tools\Console\ConsoleInputMigratorConfigurationFactory;
use Doctrine\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Doctrine\Migrations\Tools\Console\MigratorConfigurationFactory;
use Doctrine\Migrations\Version\AliasResolver;
use Doctrine\Migrations\Version\AlphabeticalComparator;
use Doctrine\Migrations\Version\Comparator;
use Doctrine\Migrations\Version\CurrentMigrationStatusCalculator;
use Doctrine\Migrations\Version\DbalExecutor;
use Doctrine\Migrations\Version\DbalMigrationFactory;
use Doctrine\Migrations\Version\DefaultAliasResolver;
use Doctrine\Migrations\Version\Executor;
use Doctrine\Migrations\Version\MigrationFactory;
use Doctrine\Migrations\Version\MigrationPlanCalculator;
use Doctrine\Migrations\Version\MigrationStatusCalculator;
use Doctrine\Migrations\Version\SortedMigrationPlanCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Stopwatch\Stopwatch;

use function array_key_exists;
use function call_user_func;
use function method_exists;
use function preg_quote;
use function sprintf;

/**
 * The DependencyFactory is responsible for wiring up and managing internal class dependencies.
 */
class DependencyFactory
{
    /** @psalm-var array<string, bool> */
    private $inResolution = [];

    /** @var Configuration */
    private $configuration;

    /** @var object[]|callable[] */
    private $dependencies = [];

    /** @var Connection */
    private $connection;

    /** @var EntityManagerInterface|null */
    private $em;

    /** @var bool */
    private $frozen = false;

    /** @var ConfigurationLoader */
    private $configurationLoader;

    /** @var ConnectionLoader */
    private $connectionLoader;

    /** @var EntityManagerLoader|null */
    private $emLoader;

    /** @var callable[] */
    private $factories = [];

    public static function fromConnection(
        ConfigurationLoader $configurationLoader,
        ConnectionLoader $connectionLoader,
        ?LoggerInterface $logger = null
    ): self {
        $dependencyFactory                      = new self($logger);
        $dependencyFactory->configurationLoader = $configurationLoader;
        $dependencyFactory->connectionLoader    = $connectionLoader;

        return $dependencyFactory;
    }

    public static function fromEntityManager(
        ConfigurationLoader $configurationLoader,
        EntityManagerLoader $emLoader,
        ?LoggerInterface $logger = null
    ): self {
        $dependencyFactory                      = new self($logger);
        $dependencyFactory->configurationLoader = $configurationLoader;
        $dependencyFactory->emLoader            = $emLoader;

        return $dependencyFactory;
    }

    private function __construct(?LoggerInterface $logger)
    {
        if ($logger === null) {
            return;
        }

        $this->setDefinition(LoggerInterface::class, static function () use ($logger): LoggerInterface {
            return $logger;
        });
    }

    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    public function freeze(): void
    {
        $this->frozen = true;
    }

    private function assertNotFrozen(): void
    {
        if ($this->frozen) {
            throw FrozenDependencies::new();
        }
    }

    public function hasEntityManager(): bool
    {
        return $this->emLoader !== null;
    }

    public function setConfigurationLoader(ConfigurationLoader $configurationLoader): void
    {
        $this->assertNotFrozen();
        $this->configurationLoader = $configurationLoader;
    }

    public function getConfiguration(): Configuration
    {
        if ($this->configuration === null) {
            $this->configuration = $this->configurationLoader->getConfiguration();
            $this->freeze();
        }

        return $this->configuration;
    }

    public function getConnection(): Connection
    {
        if ($this->connection === null) {
            $this->connection = $this->hasEntityManager()
                ? $this->getEntityManager()->getConnection()
                : $this->connectionLoader->getConnection($this->getConfiguration()->getConnectionName());
            $this->freeze();
        }

        return $this->connection;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        if ($this->em === null) {
            if ($this->emLoader === null) {
                throw MissingDependency::noEntityManager();
            }

            $this->em = $this->emLoader->getEntityManager($this->getConfiguration()->getEntityManagerName());
            $this->freeze();
        }

        return $this->em;
    }

    public function getVersionComparator(): Comparator
    {
        return $this->getDependency(Comparator::class, static function (): AlphabeticalComparator {
            return new AlphabeticalComparator();
        });
    }

    public function getLogger(): LoggerInterface
    {
        return $this->getDependency(LoggerInterface::class, static function (): LoggerInterface {
            return new NullLogger();
        });
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->getDependency(EventDispatcher::class, function (): EventDispatcher {
            return new EventDispatcher(
                $this->getConnection(),
                $this->getConnection()->getEventManager()
            );
        });
    }

    public function getClassNameGenerator(): ClassNameGenerator
    {
        return $this->getDependency(ClassNameGenerator::class, static function (): ClassNameGenerator {
            return new ClassNameGenerator();
        });
    }

    public function getSchemaDumper(): SchemaDumper
    {
        return $this->getDependency(SchemaDumper::class, function (): SchemaDumper {
            $excludedTables = [];

            $metadataConfig = $this->getConfiguration()->getMetadataStorageConfiguration();
            if ($metadataConfig instanceof TableMetadataStorageConfiguration) {
                $excludedTables[] = sprintf('/^%s$/', preg_quote($metadataConfig->getTableName(), '/'));
            }

            return new SchemaDumper(
                $this->getConnection()->getDatabasePlatform(),
                $this->getSchemaManager($this->getConnection()),
                $this->getMigrationGenerator(),
                $this->getMigrationSqlGenerator(),
                $excludedTables
            );
        });
    }

    private function getSchemaManager(Connection $connection): AbstractSchemaManager
    {
        return method_exists($connection, 'createSchemaManager')
            ? $connection->createSchemaManager()
            : $connection->getSchemaManager();
    }

    private function getEmptySchemaProvider(): SchemaProvider
    {
        return $this->getDependency(EmptySchemaProvider::class, function (): SchemaProvider {
            return new EmptySchemaProvider(
                $this->getSchemaManager($this->getConnection())
            );
        });
    }

    public function hasSchemaProvider(): bool
    {
        try {
            $this->getSchemaProvider();
        } catch (MissingDependency $exception) {
            return false;
        }

        return true;
    }

    public function getSchemaProvider(): SchemaProvider
    {
        return $this->getDependency(SchemaProvider::class, function (): SchemaProvider {
            if ($this->hasEntityManager()) {
                return new OrmSchemaProvider($this->getEntityManager());
            }

            throw MissingDependency::noSchemaProvider();
        });
    }

    public function getDiffGenerator(): DiffGenerator
    {
        return $this->getDependency(DiffGenerator::class, function (): DiffGenerator {
            return new DiffGenerator(
                $this->getConnection()->getConfiguration(),
                $this->getSchemaManager($this->getConnection()),
                $this->getSchemaProvider(),
                $this->getConnection()->getDatabasePlatform(),
                $this->getMigrationGenerator(),
                $this->getMigrationSqlGenerator(),
                $this->getEmptySchemaProvider()
            );
        });
    }

    public function getSchemaDiffProvider(): SchemaDiffProvider
    {
        return $this->getDependency(SchemaDiffProvider::class, function (): LazySchemaDiffProvider {
            return LazySchemaDiffProvider::fromDefaultProxyFactoryConfiguration(
                new DBALSchemaDiffProvider(
                    $this->getSchemaManager($this->getConnection()),
                    $this->getConnection()->getDatabasePlatform()
                )
            );
        });
    }

    private function getFileBuilder(): FileBuilder
    {
        return $this->getDependency(FileBuilder::class, static function (): FileBuilder {
            return new ConcatenationFileBuilder();
        });
    }

    private function getParameterFormatter(): ParameterFormatter
    {
        return $this->getDependency(ParameterFormatter::class, function (): ParameterFormatter {
            return new InlineParameterFormatter($this->getConnection());
        });
    }

    public function getMigrationsFinder(): MigrationFinder
    {
        return $this->getDependency(MigrationFinder::class, function (): MigrationFinder {
            $configs              = $this->getConfiguration();
            $needsRecursiveFinder = $configs->areMigrationsOrganizedByYear() || $configs->areMigrationsOrganizedByYearAndMonth();

            return $needsRecursiveFinder ? new RecursiveRegexFinder() : new GlobFinder();
        });
    }

    public function getMigrationRepository(): MigrationsRepository
    {
        return $this->getDependency(MigrationsRepository::class, function (): MigrationsRepository {
            return new FilesystemMigrationsRepository(
                $this->getConfiguration()->getMigrationClasses(),
                $this->getConfiguration()->getMigrationDirectories(),
                $this->getMigrationsFinder(),
                $this->getMigrationFactory()
            );
        });
    }

    public function getMigrationFactory(): MigrationFactory
    {
        return $this->getDependency(MigrationFactory::class, function (): MigrationFactory {
            return new DbalMigrationFactory($this->getConnection(), $this->getLogger());
        });
    }

    /**
     * @param object|callable $service
     */
    public function setService(string $id, $service): void
    {
        $this->assertNotFrozen();
        $this->dependencies[$id] = $service;
    }

    public function getMetadataStorage(): MetadataStorage
    {
        return $this->getDependency(MetadataStorage::class, function (): MetadataStorage {
            return new TableMetadataStorage(
                $this->getConnection(),
                $this->getVersionComparator(),
                $this->getConfiguration()->getMetadataStorageConfiguration(),
                $this->getMigrationRepository()
            );
        });
    }

    private function getVersionExecutor(): Executor
    {
        return $this->getDependency(Executor::class, function (): Executor {
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

    public function getQueryWriter(): QueryWriter
    {
        return $this->getDependency(QueryWriter::class, function (): QueryWriter {
            return new FileQueryWriter(
                $this->getFileBuilder(),
                $this->getLogger()
            );
        });
    }

    public function getVersionAliasResolver(): AliasResolver
    {
        return $this->getDependency(AliasResolver::class, function (): AliasResolver {
            return new DefaultAliasResolver(
                $this->getMigrationPlanCalculator(),
                $this->getMetadataStorage(),
                $this->getMigrationStatusCalculator()
            );
        });
    }

    public function getMigrationStatusCalculator(): MigrationStatusCalculator
    {
        return $this->getDependency(MigrationStatusCalculator::class, function (): MigrationStatusCalculator {
            return new CurrentMigrationStatusCalculator(
                $this->getMigrationPlanCalculator(),
                $this->getMetadataStorage()
            );
        });
    }

    public function getMigrationPlanCalculator(): MigrationPlanCalculator
    {
        return $this->getDependency(MigrationPlanCalculator::class, function (): MigrationPlanCalculator {
            return new SortedMigrationPlanCalculator(
                $this->getMigrationRepository(),
                $this->getMetadataStorage(),
                $this->getVersionComparator()
            );
        });
    }

    public function getMigrationGenerator(): Generator
    {
        return $this->getDependency(Generator::class, function (): Generator {
            return new Generator($this->getConfiguration());
        });
    }

    public function getMigrationSqlGenerator(): SqlGenerator
    {
        return $this->getDependency(SqlGenerator::class, function (): SqlGenerator {
            return new SqlGenerator(
                $this->getConfiguration(),
                $this->getConnection()->getDatabasePlatform()
            );
        });
    }

    public function getConsoleInputMigratorConfigurationFactory(): MigratorConfigurationFactory
    {
        return $this->getDependency(MigratorConfigurationFactory::class, function (): MigratorConfigurationFactory {
            return new ConsoleInputMigratorConfigurationFactory(
                $this->getConfiguration()
            );
        });
    }

    public function getMigrationStatusInfosHelper(): MigrationStatusInfosHelper
    {
        return $this->getDependency(MigrationStatusInfosHelper::class, function (): MigrationStatusInfosHelper {
            return new MigrationStatusInfosHelper(
                $this->getConfiguration(),
                $this->getConnection(),
                $this->getVersionAliasResolver(),
                $this->getMigrationPlanCalculator(),
                $this->getMigrationStatusCalculator(),
                $this->getMetadataStorage()
            );
        });
    }

    public function getMigrator(): Migrator
    {
        return $this->getDependency(Migrator::class, function (): Migrator {
            return new DbalMigrator(
                $this->getConnection(),
                $this->getEventDispatcher(),
                $this->getVersionExecutor(),
                $this->getLogger(),
                $this->getStopwatch()
            );
        });
    }

    public function getStopwatch(): Stopwatch
    {
        return $this->getDependency(Stopwatch::class, static function (): Stopwatch {
            return new Stopwatch(true);
        });
    }

    public function getRollup(): Rollup
    {
        return $this->getDependency(Rollup::class, function (): Rollup {
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
        if (! isset($this->inResolution[$id]) && array_key_exists($id, $this->factories) && ! array_key_exists($id, $this->dependencies)) {
            $this->inResolution[$id] = true;
            $this->dependencies[$id] = call_user_func($this->factories[$id], $this);
            unset($this->inResolution);
        }

        if (! array_key_exists($id, $this->dependencies)) {
            $this->dependencies[$id] = $callback();
        }

        return $this->dependencies[$id];
    }

    public function setDefinition(string $id, callable $service): void
    {
        $this->assertNotFrozen();
        $this->factories[$id] = $service;
    }
}
