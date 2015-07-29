<?php


namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
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

    public function exposed_Write($message = null)
    {
        $this->write($message);
    }

    public function exposed_ThrowIrreversibleMigrationException($message = null)
    {
        $this->throwIrreversibleMigrationException($message);
    }

    public function exposed_AddSql($sql, $params = [],  $types = [])
    {
        $this->addSql($sql, $params, $types);
    }
}
