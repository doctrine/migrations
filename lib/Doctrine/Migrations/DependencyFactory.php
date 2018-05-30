<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Finder\RecursiveRegexFinder;
use Doctrine\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Symfony\Component\Stopwatch\Stopwatch as SymfonyStopwatch;

/**
 * @internal
 */
class DependencyFactory
{
    /** @var Configuration */
    private $configuration;

    /** @var object[] */
    private $dependencies = [];

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getEventDispatcher() : EventDispatcher
    {
        return $this->getDependency(EventDispatcher::class, function () : EventDispatcher {
            return new EventDispatcher(
                $this->configuration,
                $this->getConnection()->getEventManager()
            );
        });
    }

    public function getSchemaDumper() : SchemaDumper
    {
        return $this->getDependency(SchemaDumper::class, function () : SchemaDumper {
            return new SchemaDumper(
                $this->getConnection()->getDatabasePlatform(),
                $this->getConnection()->getSchemaManager(),
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
                    $this->getConnection()->getSchemaManager(),
                    $this->getConnection()->getDatabasePlatform()
                )
            );
        });
    }

    public function getMigrationFileBuilder() : MigrationFileBuilder
    {
        return $this->getDependency(MigrationFileBuilder::class, function () : MigrationFileBuilder {
            return new MigrationFileBuilder(
                $this->getConnection()->getDatabasePlatform(),
                $this->configuration->getMigrationsTableName(),
                $this->configuration->getQuotedMigrationsColumnName(),
                $this->configuration->getQuotedMigrationsExecutedAtColumnName()
            );
        });
    }

    public function getParameterFormatter() : ParameterFormatterInterface
    {
        return $this->getDependency(ParameterFormatter::class, function () : ParameterFormatter {
            return new ParameterFormatter($this->getConnection());
        });
    }

    public function getMigrationRepository() : MigrationRepository
    {
        return $this->getDependency(MigrationRepository::class, function () : MigrationRepository {
            return new MigrationRepository(
                $this->configuration,
                $this->getConnection(),
                $this->configuration->getMigrationsFinder(),
                new VersionFactory($this->configuration, $this->getVersionExecutor())
            );
        });
    }

    public function getMigrationTableManipulator() : MigrationTableManipulator
    {
        return $this->getDependency(MigrationTableManipulator::class, function () : MigrationTableManipulator {
            return new MigrationTableManipulator(
                $this->configuration,
                $this->getConnection()->getSchemaManager(),
                $this->getMigrationTable(),
                $this->getMigrationTableStatus(),
                $this->getMigrationTableUpdater()
            );
        });
    }

    public function getMigrationTable() : MigrationTable
    {
        return $this->getDependency(MigrationTable::class, function () : MigrationTable {
            return new MigrationTable(
                $this->getConnection()->getSchemaManager(),
                $this->configuration->getMigrationsTableName(),
                $this->configuration->getMigrationsColumnName(),
                $this->configuration->getMigrationsColumnLength(),
                $this->configuration->getMigrationsExecutedAtColumnName(),
                $this->configuration->getMigrationsDirectionColumnName()
            );
        });
    }

    public function getMigrationTableStatus() : MigrationTableStatus
    {
        return $this->getDependency(MigrationTableStatus::class, function () : MigrationTableStatus {
            return new MigrationTableStatus(
                $this->getConnection()->getSchemaManager(),
                $this->getMigrationTable()
            );
        });
    }

    public function getMigrationTableUpdater() : MigrationTableUpdater
    {
        return $this->getDependency(MigrationTableUpdater::class, function () : MigrationTableUpdater {
            return new MigrationTableUpdater(
                $this->getConnection(),
                $this->getConnection()->getSchemaManager(),
                $this->getMigrationTable(),
                $this->getConnection()->getDatabasePlatform()
            );
        });
    }

    public function getVersionExecutor() : VersionExecutor
    {
        return $this->getDependency(VersionExecutor::class, function () : VersionExecutor {
            return new VersionExecutor(
                $this->configuration,
                $this->getConnection(),
                $this->getSchemaDiffProvider(),
                $this->getOutputWriter(),
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
                $this->getMigrationFileBuilder()
            );
        });
    }

    public function getOutputWriter() : OutputWriter
    {
        return $this->getDependency(OutputWriter::class, function () : OutputWriter {
            return new OutputWriter();
        });
    }

    public function getVersionAliasResolver() : VersionAliasResolver
    {
        return $this->getDependency(VersionAliasResolver::class, function () : VersionAliasResolver {
            return new VersionAliasResolver(
                $this->getMigrationRepository()
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
        return $this->getDependency(RecursiveRegexFinder::class, function () : RecursiveRegexFinder {
            return new RecursiveRegexFinder();
        });
    }

    public function getMigrationGenerator() : MigrationGenerator
    {
        return $this->getDependency(MigrationGenerator::class, function () : MigrationGenerator {
            return new MigrationGenerator($this->configuration);
        });
    }

    public function getMigrationSqlGenerator() : MigrationSqlGenerator
    {
        return $this->getDependency(MigrationSqlGenerator::class, function () : MigrationSqlGenerator {
            return new MigrationSqlGenerator(
                $this->configuration,
                $this->getConnection()->getDatabasePlatform()
            );
        });
    }

    public function getMigrationStatusInfosHelper() : MigrationStatusInfosHelper
    {
        return $this->getDependency(MigrationStatusInfosHelper::class, function () : MigrationStatusInfosHelper {
            return new MigrationStatusInfosHelper(
                $this->configuration,
                $this->getMigrationRepository()
            );
        });
    }

    public function getMigrator() : Migrator
    {
        return $this->getDependency(Migrator::class, function () : Migrator {
            return new Migrator(
                $this->configuration,
                $this->getMigrationRepository(),
                $this->getOutputWriter(),
                $this->getStopwatch()
            );
        });
    }

    public function getStopwatch() : Stopwatch
    {
        return $this->getDependency(Stopwatch::class, function () : Stopwatch {
            $symfonyStopwatch = new SymfonyStopwatch(true);

            return new Stopwatch($symfonyStopwatch);
        });
    }

    public function getRollup() : Rollup
    {
        return $this->getDependency(Rollup::class, function () : Rollup {
            return new Rollup(
                $this->configuration,
                $this->getConnection(),
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

    private function getConnection() : Connection
    {
        return $this->configuration->getConnection();
    }
}
