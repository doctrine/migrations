<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\SkipMigration;
use Doctrine\Migrations\Provider\SchemaDiffProviderInterface;
use Throwable;
use function count;
use function microtime;
use function round;
use function rtrim;
use function sprintf;
use function ucfirst;

/**
 * @internal
 */
final class VersionExecutor implements VersionExecutorInterface
{
    /** @var Configuration */
    private $configuration;

    /** @var Connection */
    private $connection;

    /** @var SchemaDiffProviderInterface */
    private $schemaProvider;

    /** @var OutputWriter */
    private $outputWriter;

    /** @var ParameterFormatterInterface */
    private $parameterFormatter;

    /** @var string[] */
    private $sql = [];

    /** @var mixed[] */
    private $params = [];

    /** @var mixed[] */
    private $types = [];

    public function __construct(
        Configuration $configuration,
        Connection $connection,
        SchemaDiffProviderInterface $schemaProvider,
        OutputWriter $outputWriter,
        ParameterFormatterInterface $parameterFormatter
    ) {
        $this->configuration      = $configuration;
        $this->connection         = $connection;
        $this->schemaProvider     = $schemaProvider;
        $this->outputWriter       = $outputWriter;
        $this->parameterFormatter = $parameterFormatter;
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
        Version $version,
        AbstractMigration $migration,
        string $direction,
        bool $dryRun = false,
        bool $timeAllQueries = false
    ) : VersionExecutionResult {
        $versionExecutionResult = new VersionExecutionResult();

        $this->startMigration($version, $migration, $direction, $dryRun);

        try {
            $this->executeMigration(
                $version,
                $migration,
                $versionExecutionResult,
                $direction,
                $dryRun,
                $timeAllQueries
            );

            $versionExecutionResult->setSql($this->sql);
            $versionExecutionResult->setParams($this->params);
            $versionExecutionResult->setTypes($this->types);
        } catch (SkipMigration $e) {
            $this->skipMigration(
                $e,
                $version,
                $migration,
                $direction,
                $dryRun
            );

            $versionExecutionResult->setSkipped(true);
        } catch (Throwable $e) {
            $this->migrationError($e, $version, $migration);

            $versionExecutionResult->setError(true);
            $versionExecutionResult->setException($e);

            throw $e;
        }

        return $versionExecutionResult;
    }

    private function startMigration(
        Version $version,
        AbstractMigration $migration,
        string $direction,
        bool $dryRun
    ) : void {
        $this->sql    = [];
        $this->params = [];
        $this->types  = [];

        $this->configuration->dispatchVersionEvent(
            $version,
            Events::onMigrationsVersionExecuting,
            $direction,
            $dryRun
        );

        if (! $migration->isTransactional()) {
            return;
        }

        //only start transaction if in transactional mode
        $this->connection->beginTransaction();
    }

    private function executeMigration(
        Version $version,
        AbstractMigration $migration,
        VersionExecutionResult $versionExecutionResult,
        string $direction,
        bool $dryRun,
        bool $timeAllQueries
    ) : VersionExecutionResult {
        $migrationStart = microtime(true);

        $version->setState(VersionState::PRE);

        $fromSchema = $this->schemaProvider->createFromSchema();

        $migration->{'pre' . ucfirst($direction)}($fromSchema);

        if ($direction === VersionDirection::UP) {
            $this->outputWriter->write("\n" . sprintf('  <info>++</info> migrating <comment>%s</comment>', $version) . "\n");
        } else {
            $this->outputWriter->write("\n" . sprintf('  <info>--</info> reverting <comment>%s</comment>', $version) . "\n");
        }

        $version->setState(VersionState::EXEC);

        $toSchema = $this->schemaProvider->createToSchema($fromSchema);

        $migration->$direction($toSchema);

        foreach ($this->schemaProvider->getSqlDiffToMigrate($fromSchema, $toSchema) as $sql) {
            $this->addSql($sql);
        }

        if (count($this->sql) !== 0) {
            if (! $dryRun) {
                $this->executeVersionExecutionResult($version, $dryRun, $timeAllQueries);
            } else {
                foreach ($this->sql as $idx => $query) {
                    $this->outputSqlQuery($idx, $query);
                }
            }
        } else {
            $this->outputWriter->write(sprintf(
                '<error>Migration %s was executed but did not result in any SQL statements.</error>',
                $version
            ));
        }

        $version->setState(VersionState::POST);

        $migration->{'post' . ucfirst($direction)}($toSchema);

        if (! $dryRun) {
            $version->markVersion($direction);
        }

        $migrationEnd = microtime(true);

        $time = round($migrationEnd - $migrationStart, 2);

        $versionExecutionResult->setTime($time);

        if ($direction === VersionDirection::UP) {
            $this->outputWriter->write(sprintf("\n  <info>++</info> migrated (%ss)", $time));
        } else {
            $this->outputWriter->write(sprintf("\n  <info>--</info> reverted (%ss)", $time));
        }

        if ($migration->isTransactional()) {
            //commit only if running in transactional mode
            $this->connection->commit();
        }

        $version->setState(VersionState::NONE);

        $this->configuration->dispatchVersionEvent(
            $version,
            Events::onMigrationsVersionExecuted,
            $direction,
            $dryRun
        );

        return $versionExecutionResult;
    }

    private function skipMigration(
        SkipMigration $e,
        Version $version,
        AbstractMigration $migration,
        string $direction,
        bool $dryRun
    ) : void {
        if ($migration->isTransactional()) {
            //only rollback transaction if in transactional mode
            $this->connection->rollBack();
        }

        if ($dryRun === false) {
            $version->markVersion($direction);
        }

        $this->outputWriter->write(sprintf("\n  <info>SS</info> skipped (Reason: %s)", $e->getMessage()));

        $version->setState(VersionState::NONE);

        $this->configuration->dispatchVersionEvent(
            $version,
            Events::onMigrationsVersionSkipped,
            $direction,
            $dryRun
        );
    }

    /**
     * @throws Throwable
     */
    private function migrationError(Throwable $e, Version $version, AbstractMigration $migration) : void
    {
        $this->outputWriter->write(sprintf(
            '<error>Migration %s failed during %s. Error %s</error>',
            $version,
            $version->getExecutionState(),
            $e->getMessage()
        ));

        if ($migration->isTransactional()) {
            //only rollback transaction if in transactional mode
            $this->connection->rollBack();
        }

        $version->setState(VersionState::NONE);
    }

    private function executeVersionExecutionResult(
        Version $version,
        bool $dryRun = false,
        bool $timeAllQueries = false
    ) : void {
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

    private function outputQueryTime(float $queryStart, bool $timeAllQueries = false) : void
    {
        if ($timeAllQueries === false) {
            return;
        }

        $queryEnd  = microtime(true);
        $queryTime = round($queryEnd - $queryStart, 4);

        $this->outputWriter->write(sprintf('  <info>%ss</info>', $queryTime));
    }

    private function outputSqlQuery(int $idx, string $query) : void
    {
        $params = $this->parameterFormatter->formatParameters(
            $this->params[$idx] ?? [],
            $this->types[$idx] ?? []
        );

        $this->outputWriter->write(rtrim(sprintf(
            '     <comment>-></comment> %s %s',
            $query,
            $params
        )));
    }
}
