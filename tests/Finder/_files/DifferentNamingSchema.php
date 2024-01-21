<?php

declare(strict_types=1);

namespace TestMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class DifferentNamingSchema extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // ignored
    }

    public function down(Schema $schema): void
    {
        // ignored
    }
}
