<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class MigrationMigrateFurther extends AbstractMigration
{

    public function down(Schema $schema)
    {
        $schema->dropTable('bar');
    }

    public function up(Schema $schema)
    {
        $table = $schema->createTable('bar');
        $table->addColumn('id', 'integer');
    }

}
