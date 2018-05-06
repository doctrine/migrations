<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Stub;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class VersionDryRunQuestionMarkParams extends AbstractMigration
{
    public function down(Schema $schema) : void
    {
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('INSERT INTO test VALUES (?, ?)', ['one', 'two']);
    }
}
