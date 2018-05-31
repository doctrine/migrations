<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;

/**
 * @internal
 */
interface SchemaDiffProviderInterface
{
    public function createFromSchema() : Schema;

    public function createToSchema(Schema $fromSchema) : Schema;

    /** @return string[] */
    public function getSqlDiffToMigrate(Schema $fromSchema, Schema $toSchema) : array;
}
