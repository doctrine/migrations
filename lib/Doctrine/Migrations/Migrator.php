<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\Migrations\Tools\BytesFormatter;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Throwable;
use const COUNT_RECURSIVE;
use function count;
use function sprintf;

class Migrator
{
    /** @var Configuration */
    private $configuration;

    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var OutputWriter */
    private $outputWriter;

    /** @var Stopwatch */
    private $stopwatch;

    public function __construct(
        Configuration $configuration,
        MigrationRepository $migrationRepository,
        OutputWriter $outputWriter,
        Stopwatch $stopwatch
    ) {
        $this->configuration       = $configuration;
        $this->migrationRepository = $migrationRepository;
        $this->outputWriter        = $outputWriter;
        $this->stopwatch           = $stopwatch;
    }

    /** @return string[][] */
    public function getSql(?string $to = null) : array
    {
        $migratorConfig = (new MigratorConfig())
            ->setDryRun(true);

        return $this->migrate($to, $migratorConfig);
    }

    public function writeSqlFile(string $path, ?string $to = null) : bool
    {
        $sql = $this->getSql($to);

        $from = $this->migrationRepository->getCurrentVersion();

        if ($to === null) {
            $to = $this->migrationRepository->getLatestVersion();
        }

        $direction = $from > $to
            ? VersionDirection::DOWN
            : VersionDirection::UP;

        $this->outputWriter->write(
            sprintf("-- Migrating from %s to %s\n", $from, $to)
        );

        /**
         * Since the configuration object changes during the creation we cannot inject things
         * properly, so I had to violate LoD here (so please, let's find a way to solve it on v2).
         */
        return $this->configuration
            ->getQueryWriter()
            ->write($path, $direction, $sql);
    }

    /**
     * @throws MigrationException
     *
     * @return string[][]
     */
    public function migrate(
        ?string $to = null,
        ?MigratorConfig $migratorConfig = null
    ) : array {
        $migratorConfig = $migratorConfig ?? new MigratorConfig();
        $dryRun         = $migratorConfig->isDryRun();

        if ($to === null) {
            $to = $this->migrationRepository->getLatestVersion();
        }

        $versions = $this->migrationRepository->getMigrations();

        if (! isset($versions[$to]) && $to > 0) {
            throw UnknownMigrationVersion::new($to);
        }

        $from = $this->migrationRepository->getCurrentVersion();

        $direction = $this->calculateDirection($from, $to);

        $migrationsToExecute = $this->configuration
            ->getMigrationsToExecute($direction, $to);

        /**
         * If
         *  there are no migrations to execute
         *  and there are migrations,
         *  and the migration from and to are the same
         * means we are already at the destination return an empty array()
         * to signify that there is nothing left to do.
         */
        if ($from === $to && count($migrationsToExecute) === 0 && count($versions) !== 0) {
            return $this->noMigrations();
        }

        $output  = $dryRun ? 'Executing dry run of migration' : 'Migrating';
        $output .= ' <info>%s</info> to <comment>%s</comment> from <comment>%s</comment>';

        $this->outputWriter->write(sprintf($output, $direction, $to, $from));

        /**
         * If there are no migrations to execute throw an exception.
         */
        if (count($migrationsToExecute) === 0 && ! $migratorConfig->getNoMigrationException()) {
            throw NoMigrationsToExecute::new();
        } elseif (count($migrationsToExecute) === 0) {
            return $this->noMigrations();
        }

        $stopwatchEvent = $this->stopwatch->start('migrate');

        $sql = $this->executeMigration($migrationsToExecute, $direction, $migratorConfig);

        $this->endMigration($stopwatchEvent, $migrationsToExecute, $sql);

        return $sql;
    }

    /**
     * @param Version[] $migrationsToExecute
     *
     * @return string[][]
     */
    private function executeMigration(
        array $migrationsToExecute,
        string $direction,
        MigratorConfig $migratorConfig
    ) : array {
        $dryRun = $migratorConfig->isDryRun();

        $this->configuration->dispatchMigrationEvent(Events::onMigrationsMigrating, $direction, $dryRun);

        $connection = $this->configuration->getConnection();

        $allOrNothing = $migratorConfig->isAllOrNothing();

        if ($allOrNothing) {
            $connection->beginTransaction();
        }

        try {
            $this->configuration->dispatchMigrationEvent(Events::onMigrationsMigrating, $direction, $dryRun);

            $sql  = [];
            $time = 0;

            foreach ($migrationsToExecute as $version) {
                $versionExecutionResult = $version->execute($direction, $migratorConfig);

                $sql[$version->getVersion()] = $versionExecutionResult->getSql();
                $time                       += $versionExecutionResult->getTime();
            }

            $this->configuration->dispatchMigrationEvent(Events::onMigrationsMigrated, $direction, $dryRun);
        } catch (Throwable $e) {
            if ($allOrNothing) {
                $connection->rollBack();
            }

            throw $e;
        }

        if ($allOrNothing) {
            $connection->commit();
        }

        return $sql;
    }

    /**
     * @param Version[]  $migrationsToExecute
     * @param string[][] $sql
     */
    private function endMigration(
        StopwatchEvent $stopwatchEvent,
        array $migrationsToExecute,
        array $sql
    ) : void {
        $stopwatchEvent->stop();

        $this->outputWriter->write("\n  <comment>------------------------</comment>\n");

        $this->outputWriter->write(sprintf(
            '  <info>++</info> finished in %sms',
            $stopwatchEvent->getDuration()
        ));

        $this->outputWriter->write(sprintf(
            '  <info>++</info> used %s memory',
            BytesFormatter::formatBytes($stopwatchEvent->getMemory())
        ));

        $this->outputWriter->write(sprintf(
            '  <info>++</info> %s migrations executed',
            count($migrationsToExecute)
        ));

        $this->outputWriter->write(sprintf(
            '  <info>++</info> %s sql queries',
            count($sql, COUNT_RECURSIVE) - count($sql)
        ));
    }

    private function calculateDirection(string $from, string $to) : string
    {
        return (int) $from > (int) $to ? VersionDirection::DOWN : VersionDirection::UP;
    }

    /** @return string[][] */
    private function noMigrations() : array
    {
        $this->outputWriter->write('<comment>No migrations to execute.</comment>');

        return [];
    }
}
