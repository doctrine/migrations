<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Query\Query;
use Doctrine\Migrations\Tools\BytesFormatter;
use Doctrine\Migrations\Version\Executor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Throwable;

use function count;

use const COUNT_RECURSIVE;

/**
 * The DbalMigrator class is responsible for generating and executing the SQL for a migration.
 *
 * @internal
 */
class DbalMigrator implements Migrator
{
    /** @var Stopwatch */
    private $stopwatch;

    /** @var LoggerInterface */
    private $logger;

    /** @var Executor */
    private $executor;

    /** @var Connection */
    private $connection;

    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct(
        Connection $connection,
        EventDispatcher $dispatcher,
        Executor $executor,
        LoggerInterface $logger,
        Stopwatch $stopwatch
    ) {
        $this->stopwatch  = $stopwatch;
        $this->logger     = $logger;
        $this->executor   = $executor;
        $this->connection = $connection;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return array<string, Query[]>
     */
    private function executeMigrations(
        MigrationPlanList $migrationsPlan,
        MigratorConfiguration $migratorConfiguration
    ): array {
        $allOrNothing = $migratorConfiguration->isAllOrNothing();

        if ($allOrNothing) {
            $this->connection->beginTransaction();
        }

        try {
            $this->dispatcher->dispatchMigrationEvent(Events::onMigrationsMigrating, $migrationsPlan, $migratorConfiguration);

            $sql = $this->executePlan($migrationsPlan, $migratorConfiguration);

            $this->dispatcher->dispatchMigrationEvent(Events::onMigrationsMigrated, $migrationsPlan, $migratorConfiguration);
        } catch (Throwable $e) {
            if ($allOrNothing) {
                $this->connection->rollBack();
            }

            throw $e;
        }

        if ($allOrNothing) {
            $this->connection->commit();
        }

        return $sql;
    }

    /**
     * @return array<string, Query[]>
     */
    private function executePlan(MigrationPlanList $migrationsPlan, MigratorConfiguration $migratorConfiguration): array
    {
        $sql  = [];
        $time = 0;

        foreach ($migrationsPlan->getItems() as $plan) {
            $versionExecutionResult = $this->executor->execute($plan, $migratorConfiguration);

            // capture the to Schema for the migration so we have the ability to use
            // it as the from Schema for the next migration when we are running a dry run
            // $toSchema may be null in the case of skipped migrations
            if (! $versionExecutionResult->isSkipped()) {
                $migratorConfiguration->setFromSchema($versionExecutionResult->getToSchema());
            }

            $sql[(string) $plan->getVersion()] = $versionExecutionResult->getSql();
            $time                             += $versionExecutionResult->getTime();
        }

        return $sql;
    }

    /**
     * @param array<string, Query[]> $sql
     */
    private function endMigrations(
        StopwatchEvent $stopwatchEvent,
        MigrationPlanList $migrationsPlan,
        array $sql
    ): void {
        $stopwatchEvent->stop();

        $this->logger->notice(
            'finished in {duration}ms, used {memory} memory, {migrations_count} migrations executed, {queries_count} sql queries',
            [
                'duration' => $stopwatchEvent->getDuration(),
                'memory' => BytesFormatter::formatBytes($stopwatchEvent->getMemory()),
                'migrations_count' => count($migrationsPlan),
                'queries_count' => count($sql, COUNT_RECURSIVE) - count($sql),
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function migrate(MigrationPlanList $migrationsPlan, MigratorConfiguration $migratorConfiguration): array
    {
        if (count($migrationsPlan) === 0) {
            $this->logger->notice('No migrations to execute.');

            return [];
        }

        $stopwatchEvent = $this->stopwatch->start('migrate');

        $sql = $this->executeMigrations($migrationsPlan, $migratorConfiguration);

        $this->endMigrations($stopwatchEvent, $migrationsPlan, $sql);

        return $sql;
    }
}
