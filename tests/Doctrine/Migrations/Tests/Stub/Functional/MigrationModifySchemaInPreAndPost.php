<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class MigrationModifySchemaInPreAndPost extends AbstractMigration
{
    private function addTable(Schema $schema, string $tableName): void
    {
        $table = $schema->createTable($tableName);
        $table->addColumn('id', 'integer');
    }

    public function preUp(Schema $schema): void
    {
        $this->addTable($schema, 'bar');
    }

    public function preDown(Schema $schema): void
    {
        $this->addTable($schema, 'bar');
    }

    public function postUp(Schema $schema): void
    {
        $this->addTable($schema, 'bar2');
    }

    public function postDown(Schema $schema): void
    {
        $this->addTable($schema, 'bar2');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('foo');
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('foo');
        $table->addColumn('id', 'integer');
    }
}
