<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;

/**
 * @internal
 */
class SchemaDiffProvider implements SchemaDiffProviderInterface
{
    /** @var AbstractPlatform */
    private $platform;

    /** @var AbstractSchemaManager */
    private $schemaManager;

    public function __construct(AbstractSchemaManager $schemaManager, AbstractPlatform $platform)
    {
        $this->schemaManager = $schemaManager;
        $this->platform      = $platform;
    }

    public function createFromSchema() : Schema
    {
        return $this->schemaManager->createSchema();
    }

    public function createToSchema(Schema $fromSchema) : Schema
    {
        return clone $fromSchema;
    }

    /** @return string[] */
    public function getSqlDiffToMigrate(Schema $fromSchema, Schema $toSchema) : array
    {
        return $fromSchema->getMigrateToSql($toSchema, $this->platform);
    }
}
