<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class VersionOutputSqlWithParam extends AbstractMigration
{
    /** @var int[] */
    private $param = [
        'param1' => 1,
        'param2' => 2,
        'param3' => 3,
    ];

    /** @param int[] $param */
    public function setParam(array $param) : void
    {
        $this->param = $param;
    }

    public function down(Schema $schema) : void
    {
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('Select 1 WHERE 1');
        $this->addSql('Select :param1 WHERE :param2 = :param3', $this->param);
    }
}
