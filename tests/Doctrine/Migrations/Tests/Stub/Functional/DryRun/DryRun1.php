<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub\Functional\DryRun;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class DryRun1 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $table = $schema->createTable('foo');
        $table->addColumn('id', 'integer');
    }

    public function down(Schema $schema) : void
    {
        $schema->dropTable('foo');
    }
}
