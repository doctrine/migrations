<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class VersionOutputSql extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        $this->addSql('Select 1');
    }

    public function up(Schema $schema): void
    {
        $this->addSql('Select 1');
    }
}
