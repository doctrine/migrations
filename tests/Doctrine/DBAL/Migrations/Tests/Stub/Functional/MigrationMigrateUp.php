<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class MigrationMigrateUp extends AbstractMigration
{
    public function down(Schema $schema)
    {
        $schema->dropTable('foo');
    }

    public function up(Schema $schema)
    {
        $table = $schema->createTable('foo');
        $table->addColumn('id', 'integer');
    }
}
