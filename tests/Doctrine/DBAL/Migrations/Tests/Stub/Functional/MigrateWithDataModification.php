<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class MigrateWithDataModification extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql('INSERT INTO test_data_migration (test) VALUES (1), (2), (3)');
    }

    public function down(Schema $schema)
    {
        $this->addSql('DELETE FROM test_data_migration');
    }

    public function isTransactional()
    {
        return true;
    }
}
