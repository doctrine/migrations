<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;

/**
 * The LazySchemaDiffProvider is responsible for lazily generating the from schema when diffing two schemas
 * to produce a migration.
 *
 * @internal
 */
class LazySchemaDiffProvider implements SchemaDiffProvider
{
    public function __construct(
        private readonly SchemaDiffProvider $originalSchemaManipulator,
    ) {
    }

    public function createFromSchema(): Schema
    {
        $originalSchemaManipulator = $this->originalSchemaManipulator;

        return LazySchema::createLazyProxy(static fn () => $originalSchemaManipulator->createFromSchema());
    }

    public function createToSchema(Schema $fromSchema): Schema
    {
        $originalSchemaManipulator = $this->originalSchemaManipulator;

        if ($fromSchema instanceof LazySchema && ! $fromSchema->isLazyObjectInitialized()) {
            return LazySchema::createLazyProxy(static fn () => $originalSchemaManipulator->createToSchema($fromSchema));
        }

        return $this->originalSchemaManipulator->createToSchema($fromSchema);
    }

    /** @return string[] */
    public function getSqlDiffToMigrate(Schema $fromSchema, Schema $toSchema): array
    {
        if (
            $toSchema instanceof LazySchema
            && ! $toSchema->isLazyObjectInitialized()
        ) {
            return [];
        }

        return $this->originalSchemaManipulator->getSqlDiffToMigrate($fromSchema, $toSchema);
    }
}
