<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class DummyMigration
 *
 * @author Robbert van den Bogerd <rvdbogerd@ibuildings.nl>
 */
class VersionDummyException extends AbstractMigration
{
    public function up(Schema $schema)
    {
        throw new \Exception('Super Exception');
    }

    public function down(Schema $schema)
    {
        throw new \Exception('Super Exception');
    }
}
