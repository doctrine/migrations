<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\MigrationRepository\Migrations\A;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class B extends AbstractMigration
{
    public function up(Schema $schema): void
    {
    }

    public function down(Schema $schema): void
    {
    }
}
