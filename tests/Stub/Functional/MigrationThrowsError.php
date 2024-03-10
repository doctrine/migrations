<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;

class MigrationThrowsError extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        throw new Exception('Migration up throws exception.');
    }

    public function down(Schema $schema): void
    {
        throw new Exception('Migration down throws exception.');
    }
}
