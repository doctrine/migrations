<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionDryRunTypes extends AbstractMigration
{
    /** @var int[] */
    private $value;

    /** @var int[] */
    private $type;

    /**
     * @param int[] $value
     * @param int[] $type
     */
    public function setParam(array $value, array $type) : void
    {
        $this->value = $value;
        $this->type  = $type;
    }

    public function down(Schema $schema) : void
    {
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('INSERT INTO test VALUES (?)', $this->value, $this->type);
    }
}
