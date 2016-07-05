<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionDryRunNamedParams extends AbstractMigration
{
    public function down(Schema $schema)
    {
    }

    public function up(Schema $schema)
    {
        $this->addSql('INSERT INTO test VALUES (:one, :two)', [
            'one' => 'one',
            'two' => 'two',
        ]);
    }
}
