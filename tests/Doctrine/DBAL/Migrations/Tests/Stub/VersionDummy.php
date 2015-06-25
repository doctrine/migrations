<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class DummyMigration
 *
 * @author Robbert van den Bogerd <rvdbogerd@ibuildings.nl>
 */
class VersionDummy extends AbstractMigration
{
    public function up(Schema $schema)
    {
    }

    public function down(Schema $schema)
    {
    }
}
