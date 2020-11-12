<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class MigrationMigrateUp extends AbstractMigration
{
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
