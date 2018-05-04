<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionDryRunWithoutParams extends AbstractMigration
{
    public function down(Schema $schema) : void
    {
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('SELECT 1 WHERE 1');
    }
}
