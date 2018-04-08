<?php

namespace Doctrine\DBAL\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;

/**
 * A schemea provider implementation that just returns the schema its given.
 */
final class StubSchemaProvider implements SchemaProviderInterface
{
    /** @var     Schema */
    private $toSchema;

    public function __construct(Schema $schema)
    {
        $this->toSchema = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        return $this->toSchema;
    }
}
