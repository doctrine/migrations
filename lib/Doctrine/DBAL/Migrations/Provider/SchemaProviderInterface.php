<?php

namespace Doctrine\DBAL\Migrations\Provider;

/**
 * Generates `Schema` objects for the diff command. A schema provider should
 * return the schema to which the database should be migrated.
 *
 * @since   1.0.0-alpha3
 */
interface SchemaProviderInterface
{
    /**
     * Create the schema to which the database should be migrated.
     *
     * @return  \Doctrine\DBAL\Schema\Schema
     */
    public function createSchema();
}
