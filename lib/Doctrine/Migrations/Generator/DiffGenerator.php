<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Generator;

use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Generator\Exception\NoChangesDetected;
use Doctrine\Migrations\Provider\SchemaProvider;

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
    private DBALConfiguration $dbalConfiguration;

    /** @var AbstractSchemaManager<AbstractPlatform> */
    private AbstractSchemaManager $schemaManager;

    private SchemaProvider $schemaProvider;

    private AbstractPlatform $platform;

    private Generator $migrationGenerator;

    private SqlGenerator $migrationSqlGenerator;

    private SchemaProvider $emptySchemaProvider;

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     */
    public function __construct(
        DBALConfiguration $dbalConfiguration,
        AbstractSchemaManager $schemaManager,
        SchemaProvider $schemaProvider,
        AbstractPlatform $platform,
        Generator $migrationGenerator,
        SqlGenerator $migrationSqlGenerator,
        SchemaProvider $emptySchemaProvider
    ) {
        $this->dbalConfiguration     = $dbalConfiguration;
        $this->schemaManager         = $schemaManager;
        $this->schemaProvider        = $schemaProvider;
        $this->platform              = $platform;
        $this->migrationGenerator    = $migrationGenerator;
        $this->migrationSqlGenerator = $migrationSqlGenerator;
        $this->emptySchemaProvider   = $emptySchemaProvider;
    }

    /**
     * @throws NoChangesDetected
     */
    public function generate(
        string $fqcn,
        ?string $filterExpression,
        bool $formatted = false,
        int $lineLength = 120,
        bool $checkDbPlatform = true,
        bool $fromEmptySchema = false
    ): string {
        if ($filterExpression !== null) {
            $this->dbalConfiguration->setSchemaAssetsFilter(
                static function ($assetName) use ($filterExpression) {
                    if ($assetName instanceof AbstractAsset) {
                        $assetName = $assetName->getName();
                    }

                    return preg_match($filterExpression, $assetName);
                }
            );
        }

        $fromSchema = $fromEmptySchema
            ? $this->createEmptySchema()
            : $this->createFromSchema();

        $toSchema = $this->createToSchema();

        $comparator = $this->schemaManager->createComparator();

        $upSql = $comparator->compareSchemas($fromSchema, $toSchema)->toSql($this->platform);

        $up = $this->migrationSqlGenerator->generate(
            $upSql,
            $formatted,
            $lineLength,
            $checkDbPlatform
        );

        $downSql = $comparator->compareSchemas($toSchema, $fromSchema)->toSql($this->platform);

        $down = $this->migrationSqlGenerator->generate(
            $downSql,
            $formatted,
            $lineLength,
            $checkDbPlatform
        );

        if ($up === '' && $down === '') {
            throw NoChangesDetected::new();
        }

        return $this->migrationGenerator->generateMigration(
            $fqcn,
            $up,
            $down
        );
    }

    private function createEmptySchema(): Schema
    {
        return $this->emptySchemaProvider->createSchema();
    }

    private function createFromSchema(): Schema
    {
        return $this->schemaManager->createSchema();
    }

    private function createToSchema(): Schema
    {
        $toSchema = $this->schemaProvider->createSchema();

        $schemaAssetsFilter = $this->dbalConfiguration->getSchemaAssetsFilter();

        if ($schemaAssetsFilter !== null) {
            foreach ($toSchema->getTables() as $table) {
                $tableName = $table->getName();

                if ($schemaAssetsFilter($this->resolveTableName($tableName))) {
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
    private function resolveTableName(string $name): string
    {
        $pos = strpos($name, '.');

        return $pos === false ? $name : substr($name, $pos + 1);
    }
}
