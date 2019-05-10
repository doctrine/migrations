<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub\Functional\DryRun;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class DryRun2 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $table = $schema->getTable('foo');
        $table->addColumn('bar', 'string', ['notnull' => false]);
    }

    public function down(Schema $schema) : void
    {
        $table = $schema->getTable('foo');
        $table->dropColumn('bar');
    }
}
