<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class VersionDummy extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
    }

    public function down(Schema $schema) : void
    {
    }
}
