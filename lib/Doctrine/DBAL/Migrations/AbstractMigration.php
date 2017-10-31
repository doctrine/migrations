<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Schema\Schema;

/**
 * Abstract class for individual migrations to extend from.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class AbstractMigration
{
    /**
     * Reference to the Version instance representing this migration
     *
     * @var Version
     */
    protected $version;

    /**
     * The Doctrine\DBAL\Connection instance we are migrating
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * Reference to the SchemaManager instance referenced by $_connection
     *
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $sm;

    /**
     * Reference to the DatabasePlatform instance referenced by $_connection
     *
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $platform;

    /**
     * The OutputWriter object instance used for outputting information
     *
     * @var OutputWriter
     */
    private $outputWriter;

    public function __construct(Version $version)
    {
        $config = $version->getConfiguration();

        $this->version      = $version;
        $this->connection   = $config->getConnection();
        $this->sm           = $this->connection->getSchemaManager();
        $this->platform     = $this->connection->getDatabasePlatform();
        $this->outputWriter = $config->getOutputWriter();
    }

    /**
     * Indicates the transactional mode of this migration.
     * If this function returns true (default) the migration will be executed in one transaction,
     * otherwise non-transactional state will be used to execute each of the migration SQLs.
     *
     * Extending class should override this function to alter the return value
     *
     * @return bool TRUE by default.
     */
    public function isTransactional()
    {
        return true;
    }

    /**
     * Get migration description
     *
     * @return string
     */
    public function getDescription()
    {
        return '';
    }

    /**
     * Print a warning message if the condition evaluates to TRUE.
     *
     * @param boolean $condition
     * @param string  $message
     */
    public function warnIf($condition, $message = '')
    {
        if ($condition) {
            $message = $message ?: 'Unknown Reason';
            $this->outputWriter->write(sprintf(
                '    <comment>Warning during %s: %s</comment>',
                $this->version->getExecutionState(),
                $message
            ));
        }
    }

    /**
     * Abort the migration if the condition evaluates to TRUE.
     *
     * @param boolean $condition
     * @param string  $message
     *
     * @throws AbortMigrationException
     */
    public function abortIf($condition, $message = '')
    {
        if ($condition) {
            throw new AbortMigrationException($message ?: 'Unknown Reason');
        }
    }

    /**
     * Skip this migration (but not the next ones) if condition evaluates to TRUE.
     *
     * @param boolean $condition
     * @param string  $message
     *
     * @throws SkipMigrationException
     */
    public function skipIf($condition, $message = '')
    {
        if ($condition) {
            throw new SkipMigrationException($message ?: 'Unknown Reason');
        }
    }

    public function preUp(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
    }

    public function preDown(Schema $schema)
    {
    }

    public function postDown(Schema $schema)
    {
    }

    abstract public function up(Schema $schema);
    abstract public function down(Schema $schema);

    protected function addSql($sql, array $params = [], array $types = [])
    {
        $this->version->addSql($sql, $params, $types);
    }

    protected function write($message)
    {
        $this->outputWriter->write($message);
    }

    protected function throwIrreversibleMigrationException($message = null)
    {
        if (null === $message) {
            $message = 'This migration is irreversible and cannot be reverted.';
        }

        throw new IrreversibleMigrationException($message);
    }
}
