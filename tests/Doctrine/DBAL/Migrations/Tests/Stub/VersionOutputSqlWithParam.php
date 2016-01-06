<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use \Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionOutputSqlWithParam extends AbstractMigration
{
    public function down(Schema $schema)
    {
    }

    public function up(Schema $schema)
    {
        $this->addSql('Select 1 WHERE 1');
        $this->addSql('Select :param1 WHERE :param2 = :param3', [
            'param1' => 1,
            'param2' => 2,
            'param3' => 3,
        ]);
    }
}
