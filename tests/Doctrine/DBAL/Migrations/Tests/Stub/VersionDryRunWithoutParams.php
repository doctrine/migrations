<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionDryRunWithoutParams extends AbstractMigration
{
    public function down(Schema $schema)
    {
    }

    public function up(Schema $schema)
    {
        $this->addSql('SELECT 1 WHERE 1');
    }
}
