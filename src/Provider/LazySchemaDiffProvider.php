<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;
use ReflectionClass;

use const PHP_VERSION_ID;

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

        if (PHP_VERSION_ID < 80400) {
            return LazySchema::createLazyProxy(static fn () => $originalSchemaManipulator->createFromSchema());
        }

        $reflector = new ReflectionClass(Schema::class);

        return $reflector->newLazyProxy(
            static fn () => $originalSchemaManipulator->createFromSchema(),
        );
    }

    public function createToSchema(Schema $fromSchema): Schema
    {
        $originalSchemaManipulator = $this->originalSchemaManipulator;

        if ($fromSchema instanceof LazySchema && ! $fromSchema->isLazyObjectInitialized()) {
            return LazySchema::createLazyProxy(static fn () => $originalSchemaManipulator->createToSchema($fromSchema));
        }

        if (PHP_VERSION_ID >= 80400) {
            $reflector = new ReflectionClass(Schema::class);

            if ($reflector->isUninitializedLazyObject($fromSchema)) {
                return $reflector->newLazyProxy(
                    static function () use ($originalSchemaManipulator, $fromSchema, $reflector) {
                        /* $this->originalSchemaManipulator may return a lazy
                         * object, for instance DBALSchemaDiffProvider just clones $fromSchema,
                         * which we know is lazy at this point of execution */
                        return $reflector->initializeLazyObject(
                            $originalSchemaManipulator->createToSchema($fromSchema),
                        );
                    },
                );
            }
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

        if (PHP_VERSION_ID >= 80400) {
            $reflector = new ReflectionClass(Schema::class);

            if ($reflector->isUninitializedLazyObject($toSchema)) {
                return [];
            }
        }

        return $this->originalSchemaManipulator->getSqlDiffToMigrate($fromSchema, $toSchema);
    }
}
