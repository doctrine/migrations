<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Connection;
use \Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionOutputSqlWithParamAndType extends AbstractMigration
{
    private $param = [
        'param1' => 1,
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
        $this->addSql('Select id WHERE id IN ( :param1 )', $this->param, $this->type);
    }
}
