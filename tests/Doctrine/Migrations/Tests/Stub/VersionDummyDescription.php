<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class DummyMigration
 */
class VersionDummyDescription extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'My super migration';
    }

    public function up(Schema $schema) : void
    {
    }

    public function down(Schema $schema) : void
    {
    }
}
