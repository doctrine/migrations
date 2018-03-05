<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class DummyMigration
 */
class VersionDummyDescription extends AbstractMigration
{
    public function getDescription()
    {
        return 'My super migration';
    }

    public function up(Schema $schema)
    {
    }

    public function down(Schema $schema)
    {
    }
}
