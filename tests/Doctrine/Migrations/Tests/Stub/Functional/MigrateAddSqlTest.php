<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class MigrateAddSqlTest extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE test_add_sql_table (test varchar(255))');
        $this->addSql('INSERT INTO test_add_sql_table (test) values (?)', ['test']);
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP TABLE test_add_sql_table');
    }
}
