<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionDryRunTypes extends AbstractMigration
{
    private $value;
    private $type;

    public function setParam($value, $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function down(Schema $schema)
    {
    }

    public function up(Schema $schema)
    {
        $this->addSql('INSERT INTO test VALUES (?)', [$this->value], [$this->type]);
    }
}
