<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class AbstractMigrationWithoutDownStub extends AbstractMigration
{
    public function up(Schema $schema): void
    {
    }
}
