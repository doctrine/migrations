<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Finder\Regression\NoVersionNamed0;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version0 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // ignored
    }

    public function down(Schema $schema) : void
    {
        // ignored
    }
}
