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
        return $this->getDependency(EventDispatcher::class, function () {
            return new EventDispatcher(
                $this->configuration,
                $this->getConnection()->getEventManager()
            );
        });
    }

    public function getSchemaDiffProvider() : SchemaDiffProviderInterface
    {
        return $this->getDependency(SchemaDiffProviderInterface::class, function () {
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
        return $this->getDependency(MigrationFileBuilder::class, function () {
            return new MigrationFileBuilder(
                $this->configuration->getMigrationsTableName(),
                $this->configuration->getQuotedMigrationsColumnName()
            );
        });
    }

    public function getParameterFormatter() : ParameterFormatterInterface
    {
        return $this->getDependency(ParameterFormatter::class, function () {
            return new ParameterFormatter($this->getConnection());
        });
    }

    public function getMigrationRepository() : MigrationRepository
    {
        return $this->getDependency(MigrationRepository::class, function () {
            return new MigrationRepository(
                $this->configuration,
                $this->getConnection(),
                $this->configuration->getMigrationsFinder(),
                new VersionFactory($this->configuration, $this->getVersionExecutor())
            );
        });
    }

    public function getMigrationTableCreator() : MigrationTableCreator
    {
        return $this->getDependency(MigrationTableCreator::class, function () {
            return new MigrationTableCreator(
                $this->configuration,
                $this->getConnection()->getSchemaManager()
            );
        });
    }

    public function getVersionExecutor() : VersionExecutor
    {
        return $this->getDependency(VersionExecutor::class, function () {
            return new VersionExecutor(
                $this->configuration,
                $this->getConnection(),
                $this->getSchemaDiffProvider(),
                $this->getOutputWriter(),
                $this->getParameterFormatter()
            );
        });
    }

    public function getQueryWriter() : FileQueryWriter
    {
        return $this->getDependency(FileQueryWriter::class, function () {
            return new FileQueryWriter(
                $this->getOutputWriter(),
                $this->getMigrationFileBuilder()
            );
        });
    }

    public function getOutputWriter() : OutputWriter
    {
        return $this->getDependency(OutputWriter::class, function () {
            return new OutputWriter();
        });
    }

    public function getVersionAliasResolver() : VersionAliasResolver
    {
        return $this->getDependency(VersionAliasResolver::class, function () {
            return new VersionAliasResolver(
                $this->getMigrationRepository()
            );
        });
    }

    public function getMigrationPlanCalculator() : MigrationPlanCalculator
    {
        return $this->getDependency(MigrationPlanCalculator::class, function () {
            return new MigrationPlanCalculator($this->getMigrationRepository());
        });
    }

    public function getRecursiveRegexFinder() : RecursiveRegexFinder
    {
        return $this->getDependency(RecursiveRegexFinder::class, function () {
            return new RecursiveRegexFinder();
        });
    }

    public function getMigrationGenerator() : MigrationGenerator
    {
        return $this->getDependency(MigrationGenerator::class, function () {
            return new MigrationGenerator($this->configuration);
        });
    }

    public function getMigrationSqlGenerator() : MigrationSqlGenerator
    {
        return $this->getDependency(MigrationSqlGenerator::class, function () {
            return new MigrationSqlGenerator(
                $this->configuration,
                $this->getConnection()->getDatabasePlatform()
            );
        });
    }

    public function getMigrationStatusInfosHelper() : MigrationStatusInfosHelper
    {
        return $this->getDependency(MigrationStatusInfosHelper::class, function () {
            return new MigrationStatusInfosHelper(
                $this->configuration,
                $this->getMigrationRepository()
            );
        });
    }

    public function getMigration() : Migration
    {
        return $this->getDependency(Migration::class, function () {
            return new Migration(
                $this->configuration,
                $this->getMigrationRepository(),
                $this->getOutputWriter()
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
