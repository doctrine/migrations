<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Schema\Schema;

class MigrationSkipMigration extends MigrationMigrateUp
{
    public function preUp(Schema $schema): void
    {
        $this->skipIf(true);
    }

    public function preDown(Schema $schema): void
    {
        $this->skipIf(true);
    }
}
