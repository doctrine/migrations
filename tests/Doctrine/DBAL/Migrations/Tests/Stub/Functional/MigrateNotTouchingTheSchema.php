<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class MigrateNotTouchingTheSchema extends AbstractMigration
{
    public function preUp(Schema $schema) : void
    {
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('SELECT 1');
    }

    public function postUp(Schema $schema) : void
    {
    }

    public function preDown(Schema $schema) : void
    {
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('SELECT 1');
    }

    public function postDown(Schema $schema) : void
    {
    }
}
