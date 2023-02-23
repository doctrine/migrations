<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Version\Fixture;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class TestMigrationWithStatement extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TRIGGER', executeAsStatement: true);
    }
}
