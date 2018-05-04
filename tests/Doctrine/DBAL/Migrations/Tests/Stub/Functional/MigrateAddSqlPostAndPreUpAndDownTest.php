<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tests\Stub\Functional;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use function sprintf;

class MigrateAddSqlPostAndPreUpAndDownTest extends AbstractMigration
{
    public const TABLE_NAME = 'test_add_sql_post_up_table';

    public function preUp(Schema $schema) : void
    {
        $this->addSql(
            sprintf('INSERT INTO %s (test) values (?)', self::TABLE_NAME),
            [1]
        );
    }

    public function up(Schema $schema) : void
    {
        $this->addSql(
            sprintf('INSERT INTO %s (test) values (?)', self::TABLE_NAME),
            [2]
        );
    }

    public function postUp(Schema $schema) : void
    {
        $this->addSql(
            sprintf('INSERT INTO %s (test) values (?)', self::TABLE_NAME),
            [3]
        );
    }

    public function preDown(Schema $schema) : void
    {
        $this->addSql(
            sprintf('INSERT INTO %s (test) values (?)', self::TABLE_NAME),
            [4]
        );
    }

    public function down(Schema $schema) : void
    {
        $this->addSql(
            sprintf('INSERT INTO %s (test) values (?)', self::TABLE_NAME),
            [5]
        );
    }

    public function postDown(Schema $schema) : void
    {
        $this->addSql(
            sprintf('INSERT INTO %s (test) values (?)', self::TABLE_NAME),
            [6]
        );
    }
}
