<?php

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Event\MigrationsVersionEventArgs;
use Doctrine\DBAL\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\DBAL\Migrations\Provider\SchemaDiffProvider;
use Doctrine\DBAL\Migrations\Provider\SchemaDiffProviderInterface;
use Doctrine\DBAL\Types\Type;

/**
 * Class which wraps a migration version and allows execution of the
 * individual migration version up or down method.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Version
{
    const STATE_NONE = 0;
    const STATE_PRE  = 1;
    const STATE_EXEC = 2;
    const STATE_POST = 3;

    const DIRECTION_UP   = 'up';
    const DIRECTION_DOWN = 'down';

    /**
     * The Migrations Configuration instance for this migration
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * The OutputWriter object instance used for outputting information
     *
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * The version in timestamp format (YYYYMMDDHHMMSS)
     *
     * @var string
     */
    private $version;

    /**
     * The migration instance for this version
     *
     * @var AbstractMigration
     */
    private $migration;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @var string
     */
    private $class;

    /** The array of collected SQL statements for this version */
    private $sql = [];

    /** The array of collected parameters for SQL statements for this version */
    private $params = [];

    /** The array of collected types for SQL statements for this version */
    private $types = [];

    /** The time in seconds that this migration version took to execute */
    private $time;

    /**
     * @var int
     */
    private $state = self::STATE_NONE;

    /** @var SchemaDiffProviderInterface */
    private $schemaProvider;

    public function __construct(Configuration $configuration, $version, $class, SchemaDiffProviderInterface $schemaProvider = null)
    {
        $this->configuration = $configuration;
        $this->outputWriter  = $configuration->getOutputWriter();
        $this->class         = $class;
        $this->connection    = $configuration->getConnection();
        $this->migration     = new $class($this);
        $this->version       = $version;

        if ($schemaProvider !== null) {
            $this->schemaProvider = $schemaProvider;
        }
        if ($schemaProvider === null) {
            $schemaProvider       = new SchemaDiffProvider(
                $this->connection->getSchemaManager(),
                $this->connection->getDatabasePlatform()
            );
            $this->schemaProvider = LazySchemaDiffProvider::fromDefaultProxyFactoryConfiguration($schemaProvider);
        }
    }

    /**
     * Returns the string version in the format YYYYMMDDHHMMSS
     *
     * @return string $version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Returns the Migrations Configuration object instance
     *
     * @return Configuration $configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Check if this version has been migrated or not.
     *
     * @return boolean
     */
    public function isMigrated()
    {
        return $this->configuration->hasVersionMigrated($this);
    }

    public function markMigrated()
    {
        $this->markVersion('up');
    }

    private function markVersion($direction)
    {
        $action = $direction === 'up' ? 'insert' : 'delete';

        $this->configuration->createMigrationTable();
        $this->connection->$action(
            $this->configuration->getMigrationsTableName(),
            [$this->configuration->getQuotedMigrationsColumnName() => $this->version]
        );
    }

    public function markNotMigrated()
    {
        $this->markVersion('down');
    }

    /**
     * Add some SQL queries to this versions migration
     *
     * @param array|string $sql
     * @param array        $params
     * @param array        $types
     */
    public function addSql($sql, array $params = [], array $types = [])
    {
        if (is_array($sql)) {
            foreach ($sql as $key => $query) {
                $this->sql[] = $query;
                if ( ! empty($params[$key])) {
                    $queryTypes = $types[$key] ?? [];
                    $this->addQueryParams($params[$key], $queryTypes);
                }
            }
        } else {
            $this->sql[] = $sql;
            if ( ! empty($params)) {
                $this->addQueryParams($params, $types);
            }
        }
    }

    /**
     * @param mixed[] $params Array of prepared statement parameters
     * @param string[] $types Array of the types of each statement parameters
     */
    private function addQueryParams($params, $types)
    {
        $index                = count($this->sql) - 1;
        $this->params[$index] = $params;
        $this->types[$index]  = $types;
    }

    /**
     * Write a migration SQL file to the given path
     *
     * @param string $path      The path to write the migration SQL file.
     * @param string $direction The direction to execute.
     *
     * @return boolean $written
     * @throws MigrationException
     */
    public function writeSqlFile($path, $direction = self::DIRECTION_UP)
    {
        $queries = $this->execute($direction, true);

        if ( ! empty($this->params)) {
            throw MigrationException::migrationNotConvertibleToSql($this->class);
        }

        $this->outputWriter->write("\n-- Version " . $this->version . "\n");

        $sqlQueries = [$this->version => $queries];

        /*
         * Since the configuration object changes during the creation we cannot inject things
         * properly, so I had to violate LoD here (so please, let's find a way to solve it on v2).
         */
        return $this->configuration->getQueryWriter()
                                   ->write($path, $direction, $sqlQueries);
    }

    /**
     * @return AbstractMigration
     */
    public function getMigration()
    {
        return $this->migration;
    }

    /**
     * Execute this migration version up or down and and return the SQL.
     * We are only allowing the addSql call and the schema modification to take effect in the up and down call.
     * This is necessary to ensure that the migration is revertable.
     * The schema is passed to the pre and post method only to be able to test the presence of some table, And the
     * connection that can get used trough it allow for the test of the presence of records.
     *
     * @param string  $direction      The direction to execute the migration.
     * @param boolean $dryRun         Whether to not actually execute the migration SQL and just do a dry run.
     * @param boolean $timeAllQueries Measuring or not the execution time of each SQL query.
     *
     * @return array $sql
     *
     * @throws \Exception when migration fails
     */
    public function execute($direction, $dryRun = false, $timeAllQueries = false)
    {
        $this->dispatchEvent(Events::onMigrationsVersionExecuting, $direction, $dryRun);

        $this->sql = [];

        $transaction = $this->migration->isTransactional();
        if ($transaction) {
            //only start transaction if in transactional mode
            $this->connection->beginTransaction();
        }

        try {
            $migrationStart = microtime(true);

            $this->state = self::STATE_PRE;
            $fromSchema  = $this->schemaProvider->createFromSchema();

            $this->migration->{'pre' . ucfirst($direction)}($fromSchema);

            if ($direction === self::DIRECTION_UP) {
                $this->outputWriter->write("\n" . sprintf('  <info>++</info> migrating <comment>%s</comment>', $this->version) . "\n");
            } else {
                $this->outputWriter->write("\n" . sprintf('  <info>--</info> reverting <comment>%s</comment>', $this->version) . "\n");
            }

            $this->state = self::STATE_EXEC;

            $toSchema = $this->schemaProvider->createToSchema($fromSchema);
            $this->migration->$direction($toSchema);

            $this->addSql($this->schemaProvider->getSqlDiffToMigrate($fromSchema, $toSchema));

            $this->executeRegisteredSql($dryRun, $timeAllQueries);

            $this->state = self::STATE_POST;
            $this->migration->{'post' . ucfirst($direction)}($toSchema);

            if ( ! $dryRun) {
                if ($direction === self::DIRECTION_UP) {
                    $this->markMigrated();
                } else {
                    $this->markNotMigrated();
                }
            }

            $migrationEnd = microtime(true);
            $this->time   = round($migrationEnd - $migrationStart, 2);
            if ($direction === self::DIRECTION_UP) {
                $this->outputWriter->write(sprintf("\n  <info>++</info> migrated (%ss)", $this->time));
            } else {
                $this->outputWriter->write(sprintf("\n  <info>--</info> reverted (%ss)", $this->time));
            }

            if ($transaction) {
                //commit only if running in transactional mode
                $this->connection->commit();
            }

            $this->state = self::STATE_NONE;

            $this->dispatchEvent(Events::onMigrationsVersionExecuted, $direction, $dryRun);

            return $this->sql;
        } catch (SkipMigrationException $e) {
            if ($transaction) {
                //only rollback transaction if in transactional mode
                $this->connection->rollBack();
            }

            if ($dryRun === false) {
                // now mark it as migrated
                if ($direction === self::DIRECTION_UP) {
                    $this->markMigrated();
                } else {
                    $this->markNotMigrated();
                }
            }

            $this->outputWriter->write(sprintf("\n  <info>SS</info> skipped (Reason: %s)", $e->getMessage()));

            $this->state = self::STATE_NONE;

            $this->dispatchEvent(Events::onMigrationsVersionSkipped, $direction, $dryRun);

            return [];
        } catch (\Exception $e) {
            $this->outputWriter->write(sprintf(
                '<error>Migration %s failed during %s. Error %s</error>',
                $this->version,
                $this->getExecutionState(),
                $e->getMessage()
            ));

            if ($transaction) {
                //only rollback transaction if in transactional mode
                $this->connection->rollBack();
            }

            $this->state = self::STATE_NONE;

            throw $e;
        }
    }

    public function getExecutionState()
    {
        switch ($this->state) {
            case self::STATE_PRE:
                return 'Pre-Checks';
            case self::STATE_POST:
                return 'Post-Checks';
            case self::STATE_EXEC:
                return 'Execution';
            default:
                return 'No State';
        }
    }

    private function outputQueryTime($queryStart, $timeAllQueries = false)
    {
        if ($timeAllQueries !== false) {
            $queryEnd  = microtime(true);
            $queryTime = round($queryEnd - $queryStart, 4);

            $this->outputWriter->write(sprintf("  <info>%ss</info>", $queryTime));
        }
    }

    /**
     * Returns the time this migration version took to execute
     *
     * @return integer $time The time this migration version took to execute
     */
    public function getTime()
    {
        return $this->time;
    }

    public function __toString()
    {
        return $this->version;
    }

    private function executeRegisteredSql($dryRun = false, $timeAllQueries = false)
    {
        if ( ! $dryRun) {
            if ( ! empty($this->sql)) {
                foreach ($this->sql as $key => $query) {
                    $queryStart = microtime(true);

                    $this->outputSqlQuery($key, $query);
                    if ( ! isset($this->params[$key])) {
                        $this->connection->executeQuery($query);
                    } else {
                        $this->connection->executeQuery($query, $this->params[$key], $this->types[$key]);
                    }

                    $this->outputQueryTime($queryStart, $timeAllQueries);
                }
            } else {
                $this->outputWriter->write(sprintf(
                    '<error>Migration %s was executed but did not result in any SQL statements.</error>',
                    $this->version
                ));
            }
        } else {
            foreach ($this->sql as $idx => $query) {
                $this->outputSqlQuery($idx, $query);
            }
        }
    }

    /**
     * Outputs a SQL query via the `OutputWriter`.
     *
     * @param int $idx The SQL query index. Used to look up params.
     * @param string $query the query to output
     * @return void
     */
    private function outputSqlQuery($idx, $query)
    {
        $params = $this->formatParamsForOutput(
            $this->params[$idx] ?? [],
            $this->types[$idx] ?? []
        );

        $this->outputWriter->write(rtrim(sprintf(
            '     <comment>-></comment> %s %s',
            $query,
            $params
        )));
    }

    /**
     * Formats a set of sql parameters for output with dry run.
     *
     * @param array $params The query parameters
     * @param array $types The types of the query params. Default type is a string
     * @return string|null a string of the parameters present.
     */
    private function formatParamsForOutput(array $params, array $types)
    {
        if (empty($params)) {
            return '';
        }

        $out = [];
        foreach ($params as $key => $value) {
            $type   = $types[$key] ?? 'string';
            $outval = '[' . $this->formatParameter($value, $type) . ']';
            $out[]  = is_string($key) ? sprintf(':%s => %s', $key, $outval) : $outval;
        }

        return sprintf('with parameters (%s)', implode(', ', $out));
    }

    private function dispatchEvent($eventName, $direction, $dryRun)
    {
        $this->configuration->dispatchEvent($eventName, new MigrationsVersionEventArgs(
            $this,
            $this->configuration,
            $direction,
            $dryRun
        ));
    }

    private function formatParameter($value, string $type) : ?string
    {
        if (Type::hasType($type)) {
            return Type::getType($type)->convertToDatabaseValue(
                $value,
                $this->connection->getDatabasePlatform()
            );
        }

        return $this->parameterToString($value);
    }

    private function parameterToString($value) : string
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this, 'parameterToString'], $value));
        }

        if (is_int($value) || is_string($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value === true ? 'true' : 'false';
        }

        return '?';
    }
}
