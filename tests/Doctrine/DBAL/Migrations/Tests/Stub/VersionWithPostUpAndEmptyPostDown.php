<?php

namespace Doctrine\DBAL\Migrations\Tests\Stub;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class VersionWithPostUpAndEmptyPostDown
 */
class VersionWithPostUpAndEmptyPostDown extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("CREATE TABLE test (test INT)");
    }

    public function down(Schema $schema)
    {
        $this->addSql("DROP TABLE test");
    }

    public function postUp(Schema $schema)
    {
        $this->addSql("INSERT INTO test VALUES (1)");
    }

    public function postDown(Schema $schema)
    {
    }
}
