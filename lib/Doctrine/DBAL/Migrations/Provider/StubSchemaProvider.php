<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;

final class StubSchemaProvider implements SchemaProviderInterface
{
    /** @var Schema */
    private $toSchema;

    public function __construct(Schema $schema)
    {
        $this->toSchema = $schema;
    }

    public function createSchema() : Schema
    {
        return $this->toSchema;
    }
}
