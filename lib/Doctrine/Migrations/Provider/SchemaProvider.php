<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;

/**
 * The SchemaProvider defines the interface used to create a Doctrine\DBAL\Schema\Schema instance that
 * represents the current state of your database.
 */
interface SchemaProvider
{
    public function createSchema(): Schema;
}
