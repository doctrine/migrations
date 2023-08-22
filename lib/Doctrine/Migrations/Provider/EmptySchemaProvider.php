<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;

/**
 * The EmptySchemaProvider class is responsible for creating a Doctrine\DBAL\Schema\Schema instance that
 * represents the empty state of your database.
 *
 * @internal
 */
final class EmptySchemaProvider implements SchemaProvider
{
    /** @param AbstractSchemaManager<AbstractPlatform> $schemaManager */
    public function __construct(private readonly AbstractSchemaManager $schemaManager)
    {
    }

    public function createSchema(): Schema
    {
        return new Schema([], [], $this->schemaManager->createSchemaConfig());
    }
}
