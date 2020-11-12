<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class VersionDryRunTypes extends AbstractMigration
{
    /** @var mixed[] */
    private $value;

    /** @var int[] */
    private $type;

    /**
     * @param mixed[] $value
     * @param int[]   $type
     */
    public function setParam(array $value, array $type): void
    {
        $this->value = $value;
        $this->type  = $type;
    }

    public function down(Schema $schema): void
    {
    }

    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO test VALUES (?)', $this->value, $this->type);
    }
}
