<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Generator;

use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\ComparatorConfig;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Generator\Exception\NoChangesDetected;
use Doctrine\Migrations\Provider\SchemaProvider;

use function class_exists;
use function method_exists;
use function preg_match;

/**
 * The DiffGenerator class is responsible for comparing two Doctrine\DBAL\Schema\Schema instances and generating a
 * migration class with the SQL statements needed to migrate from one schema to the other.
 *
 * @internal
 */
class DiffGenerator
{
    /** @param AbstractSchemaManager<AbstractPlatform> $schemaManager */
    public function __construct(
        private readonly DBALConfiguration $dbalConfiguration,
        private readonly AbstractSchemaManager $schemaManager,
        private readonly SchemaProvider $schemaProvider,
        private readonly AbstractPlatform $platform,
        private readonly Generator $migrationGenerator,
        private readonly SqlGenerator $migrationSqlGenerator,
        private readonly SchemaProvider $emptySchemaProvider,
    ) {
    }

    /** @throws NoChangesDetected */
    public function generate(
        string $fqcn,
        string|null $filterExpression,
        bool $formatted = false,
        bool|null $nowdocOutput = null,
        int $lineLength = 120,
        bool $checkDbPlatform = true,
        bool $fromEmptySchema = false,
    ): string {
        if ($filterExpression !== null) {
            $this->dbalConfiguration->setSchemaAssetsFilter(
                static function ($assetName) use ($filterExpression) {
                    if ($assetName instanceof AbstractAsset) {
                        $assetName = $assetName->getName();
                    }

                    return preg_match($filterExpression, $assetName);
                },
            );
        }

        $fromSchema = $fromEmptySchema
            ? $this->createEmptySchema()
            : $this->createFromSchema();

        $toSchema = $this->createToSchema();

        // prior to DBAL 4.0, the schema name was set to the first element in the search path,
        // which is not necessarily the default schema name
        if (
            ! method_exists($this->schemaManager, 'getSchemaSearchPaths')
            && $this->platform->supportsSchemas()
        ) {
            $defaultNamespace = $toSchema->getName();
            if ($defaultNamespace !== '') {
                $toSchema->createNamespace($defaultNamespace);
            }
        }

        if (class_exists(ComparatorConfig::class)) {
            $comparator = $this->schemaManager->createComparator((new ComparatorConfig())->withReportModifiedIndexes(false));
        } else {
            $comparator = $this->schemaManager->createComparator();
        }

        $upSql = $this->platform->getAlterSchemaSQL($comparator->compareSchemas($fromSchema, $toSchema));

        $up = $this->migrationSqlGenerator->generate(
            $upSql,
            $formatted,
            $nowdocOutput,
            $lineLength,
            $checkDbPlatform,
        );

        $downSql = $this->platform->getAlterSchemaSQL($comparator->compareSchemas($toSchema, $fromSchema));

        $down = $this->migrationSqlGenerator->generate(
            $downSql,
            $formatted,
            $nowdocOutput,
            $lineLength,
            $checkDbPlatform,
        );

        if ($up === '' && $down === '') {
            throw NoChangesDetected::new();
        }

        return $this->migrationGenerator->generateMigration(
            $fqcn,
            $up,
            $down,
        );
    }

    private function createEmptySchema(): Schema
    {
        return $this->emptySchemaProvider->createSchema();
    }

    private function createFromSchema(): Schema
    {
        return $this->schemaManager->introspectSchema();
    }

    private function createToSchema(): Schema
    {
        $toSchema = $this->schemaProvider->createSchema();

        $schemaAssetsFilter = $this->dbalConfiguration->getSchemaAssetsFilter();

        if ($schemaAssetsFilter !== null) {
            foreach ($toSchema->getTables() as $table) {
                $tableName = $table->getName();

                if ($schemaAssetsFilter($tableName)) {
                    continue;
                }

                $toSchema->dropTable($tableName);
            }
        }

        return $toSchema;
    }
}
