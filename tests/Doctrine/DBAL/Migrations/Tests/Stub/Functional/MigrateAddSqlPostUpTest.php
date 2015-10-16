<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

class MigrateAddSqlPostUpTest extends AbstractMigration
{
    const TABLE_NAME = 'test_add_sql_post_up_table';

    public function up(Schema $schema)
    {
        $table = $schema->createTable(self::TABLE_NAME);
        $table->addColumn('test', Type::STRING, ['length' => 64]);
    }

    public function postUp(Schema $schema)
    {
        $this->addSql(
            sprintf("INSERT INTO %s (test) values (?)", self::TABLE_NAME),
            ['test']
        );
    }

    public function down(Schema $schema)
    {
        $schema->dropTable(self::TABLE_NAME);
    }
}
