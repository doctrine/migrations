<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class MigrateAddSqlTest extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE test_add_sql_table (test varchar(255))");
        $this->addSql("INSERT INTO test_add_sql_table (test) values (?)", ['test']);
    }

    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE test_add_sql_table");
    }
}
