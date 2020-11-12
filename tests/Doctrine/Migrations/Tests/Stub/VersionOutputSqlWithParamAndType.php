<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class VersionOutputSqlWithParamAndType extends AbstractMigration
{
    /** @var mixed[] */
    private $param = ['param1' => 1];

    /** @var int[] */
    private $type = [Connection::PARAM_STR_ARRAY];

    /** @param mixed[] $param */
    public function setParam(array $param): void
    {
        $this->param = $param;
    }

    /** @param int[] $type */
    public function setType(array $type): void
    {
        $this->type = $type;
    }

    public function down(Schema $schema): void
    {
    }

    public function up(Schema $schema): void
    {
        $this->addSql('Select id WHERE id IN ( :param1 )', $this->param, $this->type);
    }
}
