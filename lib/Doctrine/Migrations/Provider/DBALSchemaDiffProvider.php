<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;

/**
 * The SchemaDiffProvider class is responsible for providing a Doctrine\DBAL\Schema\Schema instance that
 * represents the current state of your database. A clone of this Schema instance is passed to each of your migrations
 * so that you can manipulate the Schema object. Your manipulated Schema object is then compared to the original Schema
 * object to produce the SQL statements that need to be executed.
 *
 * @internal
 *
 * @see Doctrine\Migrations\Version\DbalExecutor
 */
class DBALSchemaDiffProvider implements SchemaDiffProvider
{
    private AbstractPlatform $platform;

    /** @var AbstractSchemaManager<AbstractPlatform> */
    private AbstractSchemaManager $schemaManager;

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager-
     */
    public function __construct(AbstractSchemaManager $schemaManager, AbstractPlatform $platform)
    {
        $this->schemaManager = $schemaManager;
        $this->platform      = $platform;
    }

    public function createFromSchema(): Schema
    {
        return $this->schemaManager->introspectSchema();
    }

    public function createToSchema(Schema $fromSchema): Schema
    {
        return clone $fromSchema;
    }

    /** @return string[] */
    public function getSqlDiffToMigrate(Schema $fromSchema, Schema $toSchema): array
    {
        return $this->platform->getAlterSchemaSQL(
            $this->schemaManager->createComparator()->compareSchemas($fromSchema, $toSchema)
        );
    }
}
