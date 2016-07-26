<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class MigrationModifySchemaInPreAndPost extends AbstractMigration
{

    private function addTable(Schema $schema, $tableName)
    {
        $table = $schema->createTable($tableName);
        $table->addColumn('id', 'integer');
    }

    public function preUp(Schema $schema, $isDryRun = false)
    {
        $this->addTable($schema, 'bar');
    }

    public function preDown(Schema $schema, $isDryRun = false)
    {
        $this->addTable($schema, 'bar');
    }

    public function postUp(Schema $schema, $isDryRun = false)
    {
        $this->addTable($schema, 'bar2');
    }

    public function postDown(Schema $schema, $isDryRun = false)
    {
        $this->addTable($schema, 'bar2');
    }

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
