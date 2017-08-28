<?php


namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class AbstractMigrationStub
 * @package Doctrine\DBAL\Migrations\Tests\Stub
 *
 * @author Robbert van den Bogerd <rvdbogerd@ibuildings.nl>
 */
class AbstractMigrationStub extends AbstractMigration
{

    public function up(Schema $schema)
    {
    }

    public function down(Schema $schema)
    {
    }

    public function exposedWrite($message = null)
    {
        $this->write($message);
    }

    public function exposedThrowIrreversibleMigrationException($message = null)
    {
        $this->throwIrreversibleMigrationException($message);
    }

    public function exposedAddSql($sql, $params = [], $types = [])
    {
        $this->addSql($sql, $params, $types);
    }

    public function getVersion() : Version
    {
        return $this->version;
    }
}
