<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Generator;

use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Generator\Exception\NoChangesDetected;
use Doctrine\Migrations\Provider\SchemaProviderInterface;
use function preg_match;
use function strpos;
use function substr;

/**
 * The DiffGenerator class is responsible for comparing two Doctrine\DBAL\Schema\Schema instances and generating a
 * migration class with the SQL statements needed to migrate from one schema to the other.
 *
 * @internal
 */
class DiffGenerator
{
    /** @var DBALConfiguration */
    private $dbalConfiguration;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    /** @var SchemaProviderInterface */
    private $schemaProvider;

    /** @var AbstractPlatform */
    private $platform;

    /** @var Generator */
    private $migrationGenerator;

    /** @var SqlGenerator */
    private $migrationSqlGenerator;

    public function __construct(
        DBALConfiguration $dbalConfiguration,
        AbstractSchemaManager $schemaManager,
        SchemaProviderInterface $schemaProvider,
        AbstractPlatform $platform,
        Generator $migrationGenerator,
        SqlGenerator $migrationSqlGenerator
    ) {
        $this->dbalConfiguration     = $dbalConfiguration;
        $this->schemaManager         = $schemaManager;
        $this->schemaProvider        = $schemaProvider;
        $this->platform              = $platform;
        $this->migrationGenerator    = $migrationGenerator;
        $this->migrationSqlGenerator = $migrationSqlGenerator;
    }

    /**
     * @throws NoChangesDetected
     */
    public function generate(
        string $versionNumber,
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
            throw NoChangesDetected::new();
        }

        return $this->migrationGenerator->generateMigration(
            $versionNumber,
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

                if (preg_match($filterExpression, $this->resolveTableName($tableName)) === 1) {
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
