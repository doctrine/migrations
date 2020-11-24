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
use Doctrine\Migrations\ParameterFormatter;
use Doctrine\Migrations\Provider\SchemaDiffProvider;
use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Tools\BytesFormatter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Stopwatch\Stopwatch;
use Throwable;

use function count;
use function ucfirst;

/**
 * The DbalExecutor class is responsible for executing a single migration version.
 *
 * @internal
 */
final class DbalExecutor implements Executor
{
    /** @var Connection */
    private $connection;

    /** @var SchemaDiffProvider */
    private $schemaProvider;

    /** @var ParameterFormatter */
    private $parameterFormatter;

    /** @var Stopwatch */
    private $stopwatch;

    /** @var Query[] */
    private $sql = [];

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
        ParameterFormatter $parameterFormatter,
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
     * @return Query[]
     */
    public function getSql(): array
    {
        return $this->sql;
    }

    public function addSql(Query $sqlQuery): void
    {
        $this->sql[] = $sqlQuery;
    }

    public function execute(
        MigrationPlan $plan,
        MigratorConfiguration $configuration
    ): ExecutionResult {
        $result = new ExecutionResult($plan->getVersion(), $plan->getDirection(), new DateTimeImmutable());

        $this->startMigration($plan, $configuration);

        try {
            $this->executeMigration(
                $plan,
                $result,
                $configuration
            );

            $result->setSql($this->sql);
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
    ): void {
        $this->sql = [];

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
    ): ExecutionResult {
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

        foreach ($migration->getSql() as $sqlQuery) {
            $this->addSql($sqlQuery);
        }

        foreach ($this->schemaProvider->getSqlDiffToMigrate($fromSchema, $toSchema) as $sql) {
            $this->addSql(new Query($sql));
        }

        if (count($this->sql) !== 0) {
            if (! $configuration->isDryRun()) {
                $this->executeResult($configuration);
            } else {
                foreach ($this->sql as $query) {
                    $this->outputSqlQuery($query, $configuration);
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
        $periods    = $stopwatchEvent->getPeriods();
        $lastPeriod = $periods[count($periods) - 1];

        $result->setTime((float) $lastPeriod->getDuration() / 1000);
        $result->setMemory($lastPeriod->getMemory());

        $params = [
            'version' => (string) $plan->getVersion(),
            'time' => $lastPeriod->getDuration(),
            'memory' => BytesFormatter::formatBytes($lastPeriod->getMemory()),
            'direction' => $direction === Direction::UP ? 'migrated' : 'reverted',
        ];

        $this->logger->info('Migration {version} {direction} (took {time}ms, used {memory} memory)', $params);

        if (! $configuration->isDryRun()) {
            $this->metadataStorage->complete($result);
        }

        if ($migration->isTransactional()) {
            //commit only if running in transactional mode
            $this->connection->commit();
        }

        $plan->markAsExecuted($result);
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
    private function getMigrationHeader(MigrationPlan $planItem, AbstractMigration $migration, string $direction): array
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

    private function migrationEnd(Throwable $e, MigrationPlan $plan, ExecutionResult $result, MigratorConfiguration $configuration): void
    {
        $migration = $plan->getMigration();
        if ($migration->isTransactional()) {
            //only rollback transaction if in transactional mode
            $this->connection->rollBack();
        }

        $plan->markAsExecuted($result);
        $this->logResult($e, $result, $plan);

        $this->dispatcher->dispatchVersionEvent(
            Events::onMigrationsVersionSkipped,
            $plan,
            $configuration
        );
    }

    private function logResult(Throwable $e, ExecutionResult $result, MigrationPlan $plan): void
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

    private function executeResult(MigratorConfiguration $configuration): void
    {
        foreach ($this->sql as $key => $query) {
            $this->outputSqlQuery($query, $configuration);

            $stopwatchEvent = $this->stopwatch->start('query');
            // executeQuery() must be used here because $query might return a result set, for instance REPAIR does
            $this->connection->executeQuery($query->getStatement(), $query->getParameters(), $query->getTypes());
            $stopwatchEvent->stop();

            if (! $configuration->getTimeAllQueries()) {
                continue;
            }

            $this->logger->notice('Query took {duration}ms', [
                'duration' => $stopwatchEvent->getDuration(),
            ]);
        }
    }

    private function outputSqlQuery(Query $query, MigratorConfiguration $configuration): void
    {
        $params = $this->parameterFormatter->formatParameters(
            $query->getParameters(),
            $query->getTypes()
        );

        $this->logger->log(
            $configuration->getTimeAllQueries() ? LogLevel::NOTICE : LogLevel::DEBUG,
            '{query} {params}',
            [
                'query'  => $query->getStatement(),
                'params' => $params,
            ]
        );
    }

    private function getFromSchema(MigratorConfiguration $configuration): Schema
    {
        // if we're in a dry run, use the from Schema instead of reading the schema from the database
        if ($configuration->isDryRun() && $configuration->getFromSchema() !== null) {
            return $configuration->getFromSchema();
        }

        return $this->schemaProvider->createFromSchema();
    }

    private function getExecutionStateAsString(int $state): string
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
