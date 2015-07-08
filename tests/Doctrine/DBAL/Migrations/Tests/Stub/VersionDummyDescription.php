<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class DummyMigration
 *
 * @author Robbert van den Bogerd <rvdbogerd@ibuildings.nl>
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
