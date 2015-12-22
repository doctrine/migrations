<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use \Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionOutputSql extends AbstractMigration
{
    public function down(Schema $schema)
    {
        $this->addSql('Select 1');
    }

    public function up(Schema $schema)
    {
        $this->addSql('Select 1');
    }
}
