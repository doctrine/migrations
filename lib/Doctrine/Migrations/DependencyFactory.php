<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Generator\FileBuilder;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Generator\SqlGenerator;
use Doctrine\Migrations\Metadata\MetadataStorage;
use Doctrine\Migrations\Metadata\TableMetadataStorage;
use Doctrine\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Doctrine\Migrations\Version\AliasResolver;
use Doctrine\Migrations\Version\Executor;
use Doctrine\Migrations\Version\Factory;
use Psr\Log\LoggerInterface;
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

    public function __construct(Configuration $configuration, Connection $connection, LoggerInterface $logger)
    {
        $this->configuration = $configuration;
        $this->logger        = $logger;
        $this->connection    = $connection;
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
                $this->configuration,
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
            return new FileBuilder(
                $this->connection->getDatabasePlatform(),
                $this->configuration->getMigrationsTableName(),
                $this->configuration->getQuotedMigrationsColumnName(),
                $this->configuration->getQuotedMigrationsExecutedAtColumnName()
            );
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
        return $this->getDependency(GlobFinder::class, static function () : MigrationFinder {
            return new GlobFinder();
        });
    }

    public function getMigrationRepository() : MigrationRepository
    {
        return $this->getDependency(MigrationRepository::class, function () : MigrationRepository {
            return new MigrationRepository(
                $this->configuration->getMigrationDirectories(),
                $this->connection,
                $this->getMigrationsFinder(),
                new Factory($this->getConnection(), $this->getVersionExecutor(), $this->getLogger())
            );
        });
    }

    public function getMetadataStorage() : MetadataStorage
    {
        return $this->getDependency(TableMetadataStorage::class, function () : MetadataStorage {
            return new TableMetadataStorage(
                $this->connection
            );
        });
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

    public function getQueryWriter() : FileQueryWriter
    {
        return $this->getDependency(FileQueryWriter::class, function () : FileQueryWriter {
            return new FileQueryWriter(
                $this->getOutputWriter(),
                $this->getFileBuilder()
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
            return new MigrationPlanCalculator($this->getMigrationRepository());
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
            return new Generator($this->configuration);
        });
    }

    public function getMigrationSqlGenerator() : SqlGenerator
    {
        return $this->getDependency(SqlGenerator::class, function () : SqlGenerator {
            return new SqlGenerator(
                $this->configuration,
                $this->connection->getDatabasePlatform()
            );
        });
    }

    public function getMigrationStatusInfosHelper() : MigrationStatusInfosHelper
    {
        return $this->getDependency(MigrationStatusInfosHelper::class, function () : MigrationStatusInfosHelper {
            return new MigrationStatusInfosHelper(
                $this->configuration,
                $this->connection,
                $this->getVersionAliasResolver()
            );
        });
    }

    public function getMigrator() : Migrator
    {
        return $this->getDependency(Migrator::class, function () : Migrator {
            return new Migrator(
                $this->connection,
                $this->getEventDispatcher(),
                $this->getMigrationPlanCalculator(),
                $this->getVersionExecutor(),
                $this->getMetadataStorage(),
                $this->getMigrationRepository(),
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
                $this->configuration,
                $this->connection,
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
