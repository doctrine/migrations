<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Metadata\MigrationPlanList;
use Doctrine\Migrations\Tools\BytesFormatter;
use Doctrine\Migrations\Version\ExecutorInterface;
use Doctrine\Migrations\Version\Version;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Throwable;
use const COUNT_RECURSIVE;
use function count;

/**
 * The Migrator class is responsible for generating and executing the SQL for a migration.
 *
 * @internal
 */
class Migrator
{
    /** @var Stopwatch */
    private $stopwatch;

    /** @var LoggerInterface */
    private $logger;

    /** @var ExecutorInterface */
    private $executor;

    /** @var Connection */
    private $connection;

    /** @var EventDispatcher */
    private $dispatcher;

    public function __construct(
        Connection $connection,
        EventDispatcher $dispatcher,
        ExecutorInterface $executor,
        LoggerInterface $logger,
        Stopwatch $stopwatch
    ) {
        $this->stopwatch               = $stopwatch;
        $this->logger                  = $logger;
        $this->executor                = $executor;
        $this->connection              = $connection;
        $this->dispatcher              = $dispatcher;
    }

    /**
     * @param MigrationPlanList $migrationsPlan
     *
     * @return string[][]
     */
    private function executeMigrations(
        MigrationPlanList $migrationsPlan,
        MigratorConfiguration $migratorConfiguration
    ) : array {
        $dryRun = $migratorConfiguration->isDryRun();

        $allOrNothing = $migratorConfiguration->isAllOrNothing();

        if ($allOrNothing) {
            $this->connection->beginTransaction();
        }

        try {
            $this->dispatcher->dispatchMigrationEvent(Events::onMigrationsMigrating, $migrationsPlan, $dryRun);

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
                $time                                        += $versionExecutionResult->getTime();
            }

            $this->dispatcher->dispatchMigrationEvent(Events::onMigrationsMigrated, $migrationsPlan, $dryRun);
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
     * @param Version[]  $migrationsToExecute
     * @param string[][] $sql
     */
    private function endMigrations(
        StopwatchEvent $stopwatchEvent,
        MigrationPlanList $migrationsPlan,
        array $sql
    ) : void {
        $stopwatchEvent->stop();

        $this->logger->info(
            'finished in {duration}ms, used {memory} memory, {migrations_count} migrations executed, {queries_count} sql queries',
            [
                'duration' => $stopwatchEvent->getDuration(),
                'memory' => BytesFormatter::formatBytes($stopwatchEvent->getMemory()),
                'migrations_count' => count($migrationsPlan),
                'queries_count' => count($sql, COUNT_RECURSIVE) - count($sql),
            ]
        );
    }

    public function migrate(MigrationPlanList $migrationsPlan, MigratorConfiguration $migratorConfiguration, Version $currentVersion = null)
    {
        /**
         * If there are no migrations to execute throw an exception.
         */
        if (count($migrationsPlan) === 0 && $migratorConfiguration->getNoMigrationException()) {
            throw NoMigrationsToExecute::new();
        }
        /**
         * If
         *  there are no migrations to execute
         *  and there are migrations,
         *  and the migration from and to are the same
         * means we are already at the destination return an empty array()
         * to signify that there is nothing left to do.
         */
        if (count($migrationsPlan) === 0) {
            return $this->noMigrations();
        }

        $dryRun = $migratorConfiguration->isDryRun();
        $this->logger->info(
            ($dryRun ? 'Executing dry run of migration' : 'Migrating') . ' {direction} to {to} from {from}',
            [
                'direction' => $migrationsPlan->getDirection(),
                'to' => (string)$migrationsPlan->getLast()->getVersion(),
                'from' => (string)$currentVersion,
            ]
        );

        $stopwatchEvent = $this->stopwatch->start('migrate');

        $sql = $this->executeMigrations($migrationsPlan, $migratorConfiguration);

        $this->endMigrations($stopwatchEvent, $migrationsPlan, $sql);

        return $sql;
    }

    /** @return string[][] */
    private function noMigrations() : array
    {
        $this->logger->info('No migrations to execute.');

        return [];
    }
}
