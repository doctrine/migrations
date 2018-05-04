<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionOutputSql extends AbstractMigration
{
    public function down(Schema $schema) : void
    {
        $this->addSql('Select 1');
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('Select 1');
    }
}
