<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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
