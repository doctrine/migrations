<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Exception;

class ExceptionVersionDummy extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        throw new Exception('Super Exception');
    }

    public function down(Schema $schema) : void
    {
        throw new Exception('Super Exception');
    }
}
