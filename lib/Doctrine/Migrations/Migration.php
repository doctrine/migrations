<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use const COUNT_RECURSIVE;
use function count;
use function sprintf;

class Migration
{
    /** @var Configuration */
    private $configuration;

    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var OutputWriter */
    private $outputWriter;

    /** @var bool */
    private $noMigrationException = false;

    public function __construct(
        Configuration $configuration,
        MigrationRepository $migrationRepository,
        OutputWriter $outputWriter
    ) {
        $this->configuration       = $configuration;
        $this->migrationRepository = $migrationRepository;
        $this->outputWriter        = $outputWriter;
    }

    public function setNoMigrationException(bool $noMigrationException = false) : void
    {
        $this->noMigrationException = $noMigrationException;
    }

    /** @return string[][] */
    public function getSql(?string $to = null) : array
    {
        return $this->migrate($to, true);
    }

    public function writeSqlFile(string $path, ?string $to = null) : bool
    {
        $sql = $this->getSql($to);

        $from = $this->migrationRepository->getCurrentVersion();

        if ($to === null) {
            $to = $this->migrationRepository->getLatestVersion();
        }

        $direction = $from > $to
            ? Version::DIRECTION_DOWN
            : Version::DIRECTION_UP;

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
        bool $dryRun = false,
        bool $timeAllQueries = false,
        ?callable $confirm = null
    ) : array {
        if ($to === null) {
            $to = $this->migrationRepository->getLatestVersion();
        }

        $from = $this->migrationRepository->getCurrentVersion();
        $to   = $to;

        $versions = $this->migrationRepository->getMigrations();

        if (! isset($versions[$to]) && $to > 0) {
            throw UnknownMigrationVersion::new($to);
        }

        $direction = $from > $to
            ? Version::DIRECTION_DOWN
            : Version::DIRECTION_UP;

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
        if ($from === $to && empty($migrationsToExecute) && ! empty($versions)) {
            return $this->noMigrations();
        }

        if (! $dryRun && $this->migrationsCanExecute($confirm) === false) {
            return [];
        }

        $output  = $dryRun ? 'Executing dry run of migration' : 'Migrating';
        $output .= ' <info>%s</info> to <comment>%s</comment> from <comment>%s</comment>';

        $this->outputWriter->write(sprintf($output, $direction, $to, $from));

        /**
         * If there are no migrations to execute throw an exception.
         */
        if (empty($migrationsToExecute) && ! $this->noMigrationException) {
            throw NoMigrationsToExecute::new();
        } elseif (empty($migrationsToExecute)) {
            return $this->noMigrations();
        }

        $this->configuration->dispatchMigrationEvent(Events::onMigrationsMigrating, $direction, $dryRun);

        $sql  = [];
        $time = 0;

        foreach ($migrationsToExecute as $version) {
            $versionExecutionResult = $version->execute($direction, $dryRun, $timeAllQueries);

            $sql[$version->getVersion()] = $versionExecutionResult->getSql();
            $time                       += $versionExecutionResult->getTime();
        }

        $this->configuration->dispatchMigrationEvent(Events::onMigrationsMigrated, $direction, $dryRun);

        $this->outputWriter->write("\n  <comment>------------------------</comment>\n");
        $this->outputWriter->write(sprintf('  <info>++</info> finished in %ss', $time));
        $this->outputWriter->write(sprintf('  <info>++</info> %s migrations executed', count($migrationsToExecute)));
        $this->outputWriter->write(sprintf('  <info>++</info> %s sql queries', count($sql, COUNT_RECURSIVE) - count($sql)));

        return $sql;
    }

    /** @return string[][] */
    private function noMigrations() : array
    {
        $this->outputWriter->write('<comment>No migrations to execute.</comment>');

        return [];
    }

    private function migrationsCanExecute(?callable $confirm = null) : bool
    {
        return $confirm === null ? true : (bool) $confirm();
    }
}
