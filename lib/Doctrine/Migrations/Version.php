<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Event\MigrationsVersionEventArgs;
use Doctrine\Migrations\Exception\MigrationNotConvertibleToSql;
use Doctrine\Migrations\Exception\SkipMigration;
use Doctrine\Migrations\Provider\LazySchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Throwable;
use function array_map;
use function count;
use function implode;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function microtime;
use function round;
use function rtrim;
use function sprintf;
use function ucfirst;

class Version
{
    public const STATE_NONE = 0;
    public const STATE_PRE  = 1;
    public const STATE_EXEC = 2;
    public const STATE_POST = 3;

    public const DIRECTION_UP   = 'up';
    public const DIRECTION_DOWN = 'down';

    /** @var Configuration */
    private $configuration;

    /** @var OutputWriter */
    private $outputWriter;

    /** @var string */
    private $version;

    /** @var AbstractMigration */
    private $migration;

    /** @var Connection */
    private $connection;

    /** @var string */
    private $class;

    /** @var string[] */
    private $sql = [];

    /** @var mixed[] */
    private $params = [];

    /** @var mixed[] */
    private $types = [];

    /** @var float */
    private $time;

    /** @var int */
    private $state = self::STATE_NONE;

    /** @var SchemaDiffProviderInterface */
    private $schemaProvider;

    public function __construct(
        Configuration $configuration,
        string $version,
        string $class,
        ?SchemaDiffProviderInterface $schemaProvider = null
    ) {
        $this->configuration = $configuration;
        $this->outputWriter  = $configuration->getOutputWriter();
        $this->class         = $class;
        $this->connection    = $configuration->getConnection();
        $this->migration     = new $class($this);
        $this->version       = $version;

        if ($schemaProvider !== null) {
            $this->schemaProvider = $schemaProvider;
        }

        if ($schemaProvider !== null) {
            return;
        }

        $schemaProvider = new SchemaDiffProvider(
            $this->connection->getSchemaManager(),
            $this->connection->getDatabasePlatform()
        );

        $this->schemaProvider = LazySchemaDiffProvider::fromDefaultProxyFactoryConfiguration(
            $schemaProvider
        );
    }

    public function getVersion() : string
    {
        return $this->version;
    }

    public function getConfiguration() : Configuration
    {
        return $this->configuration;
    }

    public function isMigrated() : bool
    {
        return $this->configuration->hasVersionMigrated($this);
    }

    public function markMigrated() : void
    {
        $this->markVersion(self::DIRECTION_UP);
    }

    public function markNotMigrated() : void
    {
        $this->markVersion(self::DIRECTION_DOWN);
    }

    /**
     * @param string[]|string $sql
     * @param mixed[]         $params
     * @param mixed[]         $types
     */
    public function addSql($sql, array $params = [], array $types = []) : void
    {
        if (is_array($sql)) {
            foreach ($sql as $key => $query) {
                $this->sql[] = $query;

                if (empty($params[$key])) {
                    continue;
                }

                $queryTypes = $types[$key] ?? [];
                $this->addQueryParams($params[$key], $queryTypes);
            }
        } else {
            $this->sql[] = $sql;

            if (! empty($params)) {
                $this->addQueryParams($params, $types);
            }
        }
    }

    public function writeSqlFile(
        string $path,
        string $direction = self::DIRECTION_UP
    ) : bool {
        $queries = $this->execute($direction, true);

        if (! empty($this->params)) {
            throw MigrationNotConvertibleToSql::new($this->class);
        }

        $this->outputWriter->write("\n-- Version " . $this->version . "\n");

        $sqlQueries = [$this->version => $queries];

        /*
         * Since the configuration object changes during the creation we cannot inject things
         * properly, so I had to violate LoD here (so please, let's find a way to solve it on v2).
         */
        return $this->configuration
            ->getQueryWriter()
            ->write($path, $direction, $sqlQueries);
    }

    public function getMigration() : AbstractMigration
    {
        return $this->migration;
    }

    /** @return string[] */
    public function execute(
        string $direction,
        bool $dryRun = false,
        bool $timeAllQueries = false
    ) : array {
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

            if (! $dryRun) {
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
        } catch (SkipMigration $e) {
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
        } catch (Throwable $e) {
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

    public function getExecutionState() : string
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

    public function getTime() : ?float
    {
        return $this->time;
    }

    public function __toString() : string
    {
        return $this->version;
    }

    private function outputQueryTime(float $queryStart, bool $timeAllQueries = false) : void
    {
        if ($timeAllQueries === false) {
            return;
        }

        $queryEnd  = microtime(true);
        $queryTime = round($queryEnd - $queryStart, 4);

        $this->outputWriter->write(sprintf('  <info>%ss</info>', $queryTime));
    }

    private function markVersion(string $direction) : void
    {
        $this->configuration->createMigrationTable();

        $migrationsColumnName = $this->configuration
            ->getQuotedMigrationsColumnName();

        if ($direction === self::DIRECTION_UP) {
            $this->connection->insert(
                $this->configuration->getMigrationsTableName(),
                [
                    $migrationsColumnName => $this->version,
                ]
            );
        } else {
            $this->connection->delete(
                $this->configuration->getMigrationsTableName(),
                [
                    $migrationsColumnName => $this->version,
                ]
            );
        }
    }

    /**
     * @param mixed[]|int $params
     * @param mixed[]|int $types
     */
    private function addQueryParams($params, $types) : void
    {
        $index                = count($this->sql) - 1;
        $this->params[$index] = $params;
        $this->types[$index]  = $types;
    }

    private function executeRegisteredSql(
        bool $dryRun = false,
        bool $timeAllQueries = false
    ) : void {
        if (! $dryRun) {
            if (! empty($this->sql)) {
                foreach ($this->sql as $key => $query) {
                    $queryStart = microtime(true);

                    $this->outputSqlQuery($key, $query);
                    if (! isset($this->params[$key])) {
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

    private function outputSqlQuery(int $idx, string $query) : void
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
     * @param mixed[] $params
     * @param mixed[] $types
     */
    private function formatParamsForOutput(array $params, array $types) : string
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

    private function dispatchEvent(
        string $eventName,
        string $direction,
        bool $dryRun
    ) : void {
        $event = $this->createMigrationsVersionEventArgs(
            $this,
            $this->configuration,
            $direction,
            $dryRun
        );

        $this->configuration->dispatchEvent($eventName, $event);
    }

    private function createMigrationsVersionEventArgs(
        Version $version,
        Configuration $config,
        string $direction,
        bool $dryRun
    ) : MigrationsVersionEventArgs {
        return new MigrationsVersionEventArgs(
            $this,
            $this->configuration,
            $direction,
            $dryRun
        );
    }

    /**
     * @param string|int $value
     * @param string|int $type
     *
     * @return string|int
     */
    private function formatParameter($value, $type)
    {
        if (is_string($type) && Type::hasType($type)) {
            return Type::getType($type)->convertToDatabaseValue(
                $value,
                $this->connection->getDatabasePlatform()
            );
        }

        return $this->parameterToString($value);
    }

    /**
     * @param int[]|bool[]|string[]|array|int|string|bool $value
     */
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
