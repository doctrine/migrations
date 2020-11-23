<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class AbstractMigrationStub extends AbstractMigration
{
    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }

    public function exposedWrite(string $message): void
    {
        $this->write($message);
    }

    public function exposedThrowIrreversibleMigrationException(?string $message = null): void
    {
        $this->throwIrreversibleMigrationException($message);
    }

    /**
     * @param int[] $params
     * @param int[] $types
     */
    public function exposedAddSql(string $sql, array $params = [], array $types = []): void
    {
        $this->addSql($sql, $params, $types);
    }
}
