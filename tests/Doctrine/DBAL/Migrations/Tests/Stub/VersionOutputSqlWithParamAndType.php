<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Connection;
use \Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionOutputSqlWithParamAndType extends AbstractMigration
{
    private $param = [
        'param1' => 1,
        'param2' => 2,
        'param3' => 3,
    ];

    private $type = [Connection::PARAM_STR_ARRAY];

    public function setParam($param)
    {
        $this->param = $param;
    }

    public function setType(array $type)
    {
        $this->type = $type;
    }

    public function down(Schema $schema)
    {
    }

    public function up(Schema $schema)
    {
        $this->addSql('Select :param1 WHERE :param2 = :param3', $this->param, $this->type);
    }
}
