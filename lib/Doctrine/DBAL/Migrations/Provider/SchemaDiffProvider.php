<?php

namespace Doctrine\DBAL\Migrations\Provider;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;

class SchemaDiffProvider implements SchemaDiffProviderInterface
{
    /** @var  AbstractPlatform */
    private $platform;

    /** @var  AbstractSchemaManager */
    private $schemaManager;

    public function __construct(AbstractSchemaManager $schemaManager, AbstractPlatform $platform)
    {
        $this->schemaManager = $schemaManager;
        $this->platform      = $platform;
    }

    /**
     * @return Schema
     */
    public function createFromSchema()
    {
        return $this->schemaManager->createSchema();
    }

    /**
     * @return Schema
     */
    public function createToSchema(Schema $fromSchema)
    {
        return clone $fromSchema;
    }

    /**
     * @return string[]
     */
    public function getSqlDiffToMigrate(Schema $fromSchema, Schema $toSchema)
    {
        return $fromSchema->getMigrateToSql($toSchema, $this->platform);
    }
}
