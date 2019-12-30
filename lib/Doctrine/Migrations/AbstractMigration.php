<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Exception\AbortMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;
use Doctrine\Migrations\Exception\SkipMigration;
use Psr\Log\LoggerInterface;
use function func_get_args;

/**
 * The AbstractMigration class is for end users to extend from when creating migrations. Extend this class
 * and implement the required up() and down() methods.
 */
abstract class AbstractMigration
{
    /** @var Connection */
    protected $connection;

    /** @var AbstractSchemaManager */
    protected $sm;

    /** @var AbstractPlatform */
    protected $platform;

    /** @var LoggerInterface */
    private $logger;

    /** @var mixed[] */
    private $plannedSql = [];

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->sm         = $this->connection->getSchemaManager();
        $this->platform   = $this->connection->getDatabasePlatform();
        $this->logger     = $logger;
    }

    /**
     * Indicates the transactional mode of this migration.
     *
     * If this function returns true (default) the migration will be executed
     * in one transaction, otherwise non-transactional state will be used to
     * execute each of the migration SQLs.
     *
     * Extending class should override this function to alter the return value.
     */
    public function isTransactional() : bool
    {
        return true;
    }

    public function getDescription() : string
    {
        return '';
    }

    public function warnIf(bool $condition, string $message = '') : void
    {
        if (! $condition) {
            return;
        }

        $message = $message ?: 'Unknown Reason';

        $this->logger->warning($message, ['migration' => $this]);
    }

    /**
     * @throws AbortMigration
     */
    public function abortIf(bool $condition, string $message = '') : void
    {
        if ($condition) {
            throw new AbortMigration($message ?: 'Unknown Reason');
        }
    }

    /**
     * @throws SkipMigration
     */
    public function skipIf(bool $condition, string $message = '') : void
    {
        if ($condition) {
            throw new SkipMigration($message ?: 'Unknown Reason');
        }
    }

    public function preUp(Schema $schema) : void
    {
    }

    public function postUp(Schema $schema) : void
    {
    }

    public function preDown(Schema $schema) : void
    {
    }

    public function postDown(Schema $schema) : void
    {
    }

    abstract public function up(Schema $schema) : void;

    abstract public function down(Schema $schema) : void;

    /**
     * @param mixed[] $params
     * @param mixed[] $types
     */
    protected function addSql(
        string $sql,
        array $params = [],
        array $types = []
    ) : void {
        $this->plannedSql[] = func_get_args();
    }

    /**
     * @return mixed[]
     */
    public function getSql() : array
    {
        return $this->plannedSql;
    }

    protected function write(string $message) : void
    {
        $this->logger->notice($message, ['migration' => $this]);
    }

    protected function throwIrreversibleMigrationException(?string $message = null) : void
    {
        if ($message === null) {
            $message = 'This migration is irreversible and cannot be reverted.';
        }

        throw new IrreversibleMigration($message);
    }
}
