<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class MigrationSkipMigration extends MigrationMigrateUp
{

    public function preUp(Schema $schema)
    {
        $this->skipIf(true);
    }

    public function preDown(Schema $schema)
    {
        $this->skipIf(true);
    }
}
