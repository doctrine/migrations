<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Version;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\EventDispatcher;
use Doctrine\Migrations\Events;
use Doctrine\Migrations\Exception\SkipMigration;
use Doctrine\Migrations\Metadata\MigrationPlan;
use Doctrine\Migrations\Metadata\Storage\MetadataStorage;
use Doctrine\Migrations\MigratorConfiguration;
use Doctrine\Migrations\ParameterFormatterInterface;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Stopwatch;
use Doctrine\Migrations\Tools\BytesFormatter;
use Psr\Log\LoggerInterface;
use Throwable;
use function count;
use function ucfirst;

/**
 * The Executor class is responsible for executing a single migration version.
 *
 * @internal
 */
final class Executor implements ExecutorInterface
{
    /** @var Connection */
    private $connection;

    /** @var SchemaDiffProvider */
    private $schemaProvider;

    /** @var ParameterFormatterInterface */
    private $parameterFormatter;

    /** @var Stopwatch */
    private $stopwatch;

    /** @var array<int, string> */
    private $sql = [];

    /** @var mixed[] */
    private $params = [];

    /** @var mixed[] */
    private $types = [];

    /** @var MetadataStorage */
    private $metadataStorage;

    /** @var LoggerInterface */
    private $logger;

    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct(
        MetadataStorage $metadataStorage,
        EventDispatcher $dispatcher,
        Connection $connection,
        SchemaDiffProvider $schemaProvider,
        LoggerInterface $logger,
        ParameterFormatterInterface $parameterFormatter,
        Stopwatch $stopwatch
    ) {
        $this->connection         = $connection;
        $this->schemaProvider     = $schemaProvider;
        $this->parameterFormatter = $parameterFormatter;
        $this->stopwatch          = $stopwatch;
        $this->metadataStorage    = $metadataStorage;
        $this->logger             = $logger;
        $this->dispatcher         = $dispatcher;
    }

    /**
     * @return string[]
     */
    public function getSql() : array
    {
        return $this->sql;
    }

    /**
     * @return mixed[]
     */
    public function getParams() : array
    {
        return $this->params;
    }

    /**
     * @return mixed[]
     */
    public function getTypes() : array
    {
        return $this->types;
    }

    /**
     * @param mixed[] $params
     * @param mixed[] $types
     */
    public function addSql(string $sql, array $params = [], array $types = []) : void
    {
        $this->sql[] = $sql;

        if (count($params) === 0) {
            return;
        }

        $this->addQueryParams($params, $types);
    }

    public function execute(
        MigrationPlan $plan,
        MigratorConfiguration $configuration
    ) : ExecutionResult {
        $result = new ExecutionResult($plan->getVersion(), $plan->getDirection(), new DateTimeImmutable());

        $this->startMigration($plan, $configuration);

        try {
            $this->executeMigration(
                $plan,
                $result,
                $configuration
            );

            $result->setSql($this->sql, $this->params, $this->types);
        } catch (SkipMigration $e) {
            $result->setSkipped(true);

            $this->migrationEnd($e, $plan, $result, $configuration);
        } catch (Throwable $e) {
            $result->setError(true, $e);

            $this->migrationEnd($e, $plan, $result, $configuration);

            throw $e;
        }

        return $result;
    }

    private function startMigration(
        MigrationPlan $plan,
        MigratorConfiguration $configuration
    ) : void {
        $this->sql    = [];
        $this->params = [];
        $this->types  = [];

        $this->dispatcher->dispatchVersionEvent(
            Events::onMigrationsVersionExecuting,
            $plan,
            $configuration
        );

        if (! $plan->getMigration()->isTransactional()) {
            return;
        }

        // only start transaction if in transactional mode
        $this->connection->beginTransaction();
    }

    private function executeMigration(
        MigrationPlan $plan,
        ExecutionResult $result,
        MigratorConfiguration $configuration
    ) : ExecutionResult {
        $stopwatchEvent = $this->stopwatch->start('execute');

        $migration = $plan->getMigration();
        $direction = $plan->getDirection();

        $result->setState(State::PRE);

        $fromSchema = $this->getFromSchema($configuration);

        $migration->{'pre' . ucfirst($direction)}($fromSchema);

        $this->logger->info(...$this->getMigrationHeader($plan, $migration, $direction));

        $result->setState(State::EXEC);

        $toSchema = $this->schemaProvider->createToSchema($fromSchema);

        $result->setToSchema($toSchema);

        $migration->$direction($toSchema);

        foreach ($migration->getSql() as $sqlData) {
            $this->addSql(...$sqlData);
        }

        foreach ($this->schemaProvider->getSqlDiffToMigrate($fromSchema, $toSchema) as $sql) {
            $this->addSql($sql);
        }

        if (count($this->sql) !== 0) {
            if (! $configuration->isDryRun()) {
                $this->executeResult($configuration);
            } else {
                foreach ($this->sql as $idx => $query) {
                    $this->outputSqlQuery($idx, $query);
                }
            }
        } else {
            $this->logger->warning('Migration {version} was executed but did not result in any SQL statements.', [
                'version' => (string) $plan->getVersion(),
            ]);
        }

        $result->setState(State::POST);

        $migration->{'post' . ucfirst($direction)}($toSchema);
        $stopwatchEvent->stop();

        $result->setTime($stopwatchEvent->getDuration());
        $result->setMemory($stopwatchEvent->getMemory());
        $plan->markAsExecuted($result);

        if (! $configuration->isDryRun()) {
            $this->metadataStorage->complete($result);
        }

        $params = [
            'version' => (string) $plan->getVersion(),
            'time' => $stopwatchEvent->getDuration(),
            'memory' => BytesFormatter::formatBytes($stopwatchEvent->getMemory()),
            'direction' => $direction === Direction::UP ? 'migrated' : 'reverted',
        ];

        $this->logger->info('Migration {version} {direction} (took {time}ms, used {memory} memory)', $params);

        if ($migration->isTransactional()) {
            //commit only if running in transactional mode
            $this->connection->commit();
        }

        $result->setState(State::NONE);

        $this->dispatcher->dispatchVersionEvent(
            Events::onMigrationsVersionExecuted,
            $plan,
            $configuration
        );

        return $result;
    }

    /**
     * @return mixed[]
     */
    private function getMigrationHeader(MigrationPlan $planItem, AbstractMigration $migration, string $direction) : array
    {
        $versionInfo = (string) $planItem->getVersion();
        $description = $migration->getDescription();

        if ($description !== '') {
            $versionInfo .= ' (' . $description . ')';
        }

        $params = ['version_name' => $versionInfo];

        if ($direction === Direction::UP) {
            return ['++ migrating {version_name}', $params];
        }

        return ['++ reverting {version_name}', $params];
    }

    private function migrationEnd(Throwable $e, MigrationPlan $plan, ExecutionResult $result, MigratorConfiguration $configuration) : void
    {
        $plan->markAsExecuted($result);
        $this->logResult($e, $result, $plan);

        $this->dispatcher->dispatchVersionEvent(
            Events::onMigrationsVersionSkipped,
            $plan,
            $configuration
        );

        $migration = $plan->getMigration();

        if ($migration->isTransactional()) {
            //only rollback transaction if in transactional mode
            $this->connection->rollBack();
        }

        if ($configuration->isDryRun() || $result->isSkipped() || $result->hasError()) {
            return;
        }

        $this->metadataStorage->complete($result);
    }

    private function logResult(Throwable $e, ExecutionResult $result, MigrationPlan $plan) : void
    {
        if ($result->isSkipped()) {
            $this->logger->error(
                'Migration {version} skipped during {state}. Reason: "{reason}"',
                [
                    'version' => (string) $plan->getVersion(),
                    'reason' => $e->getMessage(),
                    'state' => $this->getExecutionStateAsString($result->getState()),
                ]
            );
        } elseif ($result->hasError()) {
            $this->logger->error(
                'Migration {version} failed during {state}. Error: "{error}"',
                [
                    'version' => (string) $plan->getVersion(),
                    'error' => $e->getMessage(),
                    'state' => $this->getExecutionStateAsString($result->getState()),
                ]
            );
        }
    }

    private function executeResult(MigratorConfiguration $configuration) : void
    {
        foreach ($this->sql as $key => $query) {
            $stopwatchEvent = $this->stopwatch->start('query');

            $this->outputSqlQuery($key, $query);

            if (! isset($this->params[$key])) {
                $this->connection->executeQuery($query);
            } else {
                $this->connection->executeQuery($query, $this->params[$key], $this->types[$key]);
            }

            $stopwatchEvent->stop();

            if (! $configuration->getTimeAllQueries()) {
                continue;
            }

            $this->logger->debug('{duration}ms', [
                'duration' => $stopwatchEvent->getDuration(),
            ]);
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

    private function outputSqlQuery(int $idx, string $query) : void
    {
        $params = $this->parameterFormatter->formatParameters(
            $this->params[$idx] ?? [],
            $this->types[$idx] ?? []
        );

        $this->logger->debug('{query} {params}', [
            'query' => $query,
            'params' => $params,
        ]);
    }

    private function getFromSchema(MigratorConfiguration $configuration) : Schema
    {
        // if we're in a dry run, use the from Schema instead of reading the schema from the database
        if ($configuration->isDryRun() && $configuration->getFromSchema() !== null) {
            return $configuration->getFromSchema();
        }

        return $this->schemaProvider->createFromSchema();
    }

    private function getExecutionStateAsString(int $state) : string
    {
        switch ($state) {
            case State::PRE:
                return 'Pre-Checks';
            case State::POST:
                return 'Post-Checks';
            case State::EXEC:
                return 'Execution';
            default:
                return 'No State';
        }
    }
}
