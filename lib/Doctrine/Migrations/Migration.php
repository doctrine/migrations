<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;

interface Migration
{
    /**
     * Indicates the transactional mode of this migration.
     *
     * If this function returns true the migration will be executed
     * in one transaction, otherwise non-transactional state will be used to
     * execute each of the migration SQLs.
     */
    public function isTransactional() : bool;

    public function getDescription() : string;

    public function warnIf(bool $condition, string $message = '') : void;

    /**
     * @throws AbortMigration
     */
    public function abortIf(bool $condition, string $message = '') : void;

    /**
     * @throws SkipMigration
     */
    public function skipIf(bool $condition, string $message = '') : void;

    public function preUp(Schema $schema) : void;

    public function postUp(Schema $schema) : void;

    public function preDown(Schema $schema) : void;

    public function postDown(Schema $schema) : void;

    public function up(Schema $schema) : void;

    public function down(Schema $schema) : void;

    /**
     * @param mixed[] $params
     * @param mixed[] $types
     */
    public function addSql(
        string $sql,
        array $params = [],
        array $types = []
    ) : void;

    public function write(string $message) : void;

    public function throwIrreversibleMigrationException(?string $message = null) : void;
}
