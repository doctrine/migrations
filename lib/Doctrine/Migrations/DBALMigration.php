<?php

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;

/**
 * Interface for Developers to implement for PHP Based Doctrine-DBAL Migrations.
 */
interface DBALMigration
{
    public function migrate(Connection $connection);
}
