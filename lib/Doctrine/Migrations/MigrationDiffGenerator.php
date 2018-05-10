<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Provider\SchemaProviderInterface;
use RuntimeException;
use function preg_match;
use function strpos;
use function substr;

class MigrationDiffGenerator
{
    /** @var Configuration */
    private $configuration;

    /** @var DBALConfiguration */
    private $dbalConfiguration;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var SchemaProviderInterface */
    private $schemaProvider;

    /** @var AbstractPlatform */
    private $platform;

    /** @var MigrationGenerator */
    private $migrationGenerator;

    /** @var MigrationSqlGenerator */
    private $migrationSqlGenerator;

    public function __construct(
        Configuration $configuration,
        DBALConfiguration $dbalConfiguration,
        AbstractSchemaManager $schemaManager,
        SchemaProviderInterface $schemaProvider,
        AbstractPlatform $platform,
        MigrationGenerator $migrationGenerator,
        MigrationSqlGenerator $migrationSqlGenerator
    ) {
        $this->configuration         = $configuration;
        $this->dbalConfiguration     = $dbalConfiguration;
        $this->schemaManager         = $schemaManager;
        $this->schemaProvider        = $schemaProvider;
        $this->platform              = $platform;
        $this->migrationGenerator    = $migrationGenerator;
        $this->migrationSqlGenerator = $migrationSqlGenerator;
    }

    public function generate(
        ?string $filterExpression,
        bool $formatted = false,
        int $lineLength = 120
    ) : string {
        if ($filterExpression !== null) {
            $this->dbalConfiguration->setFilterSchemaAssetsExpression($filterExpression);
        }

        $fromSchema = $this->createFromSchema();

        $toSchema = $this->createToSchema();

        $up = $this->migrationSqlGenerator->generate(
            $fromSchema->getMigrateToSql($toSchema, $this->platform),
            $formatted,
            $lineLength
        );

        $down = $this->migrationSqlGenerator->generate(
            $fromSchema->getMigrateFromSql($toSchema, $this->platform),
            $formatted,
            $lineLength
        );

        if ($up === '' && $down === '') {
            throw new RuntimeException('No changes detected in your mapping information.');
        }

        return $this->migrationGenerator->generateMigration(
            $this->configuration->generateVersionNumber(),
            $up,
            $down
        );
    }

    private function createFromSchema() : Schema
    {
        return $this->schemaManager->createSchema();
    }

    private function createToSchema() : Schema
    {
        $toSchema = $this->schemaProvider->createSchema();

        $filterExpression = $this->dbalConfiguration->getFilterSchemaAssetsExpression();

        if ($filterExpression !== null) {
            foreach ($toSchema->getTables() as $table) {
                $tableName = $table->getName();

                if (preg_match($filterExpression, $this->resolveTableName($tableName))) {
                    continue;
                }

                $toSchema->dropTable($tableName);
            }
        }

        return $toSchema;
    }

    /**
     * Resolve a table name from its fully qualified name. The `$name` argument
     * comes from Doctrine\DBAL\Schema\Table#getName which can sometimes return
     * a namespaced name with the form `{namespace}.{tableName}`. This extracts
     * the table name from that.
     */
    private function resolveTableName(string $name) : string
    {
        $pos = strpos($name, '.');

        return $pos === false ? $name : substr($name, $pos + 1);
    }
}
