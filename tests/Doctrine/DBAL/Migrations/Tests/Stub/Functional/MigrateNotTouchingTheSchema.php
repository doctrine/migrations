<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class MigrateNotTouchingTheSchema extends AbstractMigration
{
    public function preUp(Schema $schema)
    {

    }

    public function up(Schema $schema)
    {
        $this->addSql("SELECT 1");
    }

    public function postUp(Schema $schema)
    {

    }

    public function preDown(Schema $schema)
    {

    }

    public function down(Schema $schema)
    {
        $this->addSql("SELECT 1");
    }

    public function postDown(Schema $schema)
    {

    }
}
