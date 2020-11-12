<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version2Test extends AbstractMigration
{
    public function down(Schema $schema): void
    {
    }

    public function up(Schema $schema): void
    {
    }
}
