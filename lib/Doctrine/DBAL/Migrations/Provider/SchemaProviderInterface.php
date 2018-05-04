<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;

interface SchemaProviderInterface
{
    public function createSchema() : Schema;
}
