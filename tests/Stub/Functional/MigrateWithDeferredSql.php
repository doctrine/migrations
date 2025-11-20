<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class MigrateWithDeferredSql extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Last to be executed
        $this->addDeferredSql('INSERT INTO test(id) VALUES(123)');

        // Executed after queries from addSql()
        $schema->createTable('test')->addColumn('id', 'integer');

        // First to be executed
        $this->addSql('SELECT 1');
    }
}
