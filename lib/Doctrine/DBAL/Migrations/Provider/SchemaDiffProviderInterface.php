<?php

namespace Doctrine\DBAL\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;

/**
 * Generates `Schema` objects to be passed to the migrations class.
 */
interface SchemaDiffProviderInterface
{
    /**
     * Create the schema that represent the current state of the database.
     *
     * @return Schema
     */
    public function createFromSchema();

    /**
     * Create the schema that will represent the future state of the database
     *
     * @return Schema
     */
    public function createToSchema(Schema $fromSchema);

    /**
     * Return an array of sql statement that migrate the database state from the
     * fromSchema to the toSchema.
     *
     * @return string[]
     */
    public function getSqlDiffToMigrate(Schema $fromSchema, Schema $toSchema);
}
