<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Exception\MigrationNotConvertibleToSql;
use function assert;
use function count;
use function in_array;
use function str_replace;

class Version implements VersionInterface
{
    public const STATE_NONE = 0;
    public const STATE_PRE  = 1;
    public const STATE_EXEC = 2;
    public const STATE_POST = 3;

    public const DIRECTION_UP   = 'up';
    public const DIRECTION_DOWN = 'down';

    private const STATES = [
        self::STATE_NONE,
        self::STATE_PRE,
        self::STATE_EXEC,
        self::STATE_POST,
    ];

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

    /** @var int */
    private $state = self::STATE_NONE;

    /** @var VersionExecutorInterface */
    private $versionExecutor;

    public function __construct(
        Configuration $configuration,
        string $version,
        string $class,
        VersionExecutorInterface $versionExecutor
    ) {
        $this->configuration   = $configuration;
        $this->outputWriter    = $configuration->getOutputWriter();
        $this->class           = $class;
        $this->connection      = $configuration->getConnection();
        $this->migration       = new $class($this);
        $this->version         = $version;
        $this->versionExecutor = $versionExecutor;
    }

    public function __toString() : string
    {
        return $this->version;
    }

    public function getVersion() : string
    {
        return $this->version;
    }

    public function getDateTime() : string
    {
        $datetime = str_replace('Version', '', $this->version);
        $datetime = DateTime::createFromFormat('YmdHis', $datetime);

        if ($datetime === false) {
            return '';
        }

        return $datetime->format('Y-m-d H:i:s');
    }

    public function getConfiguration() : Configuration
    {
        return $this->configuration;
    }

    public function getMigration() : AbstractMigration
    {
        return $this->migration;
    }

    public function isMigrated() : bool
    {
        return $this->configuration->hasVersionMigrated($this);
    }

    public function setState(int $state) : void
    {
        assert(in_array($state, self::STATES, true));

        $this->state = $state;
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

    /**
     * @param mixed[] $params
     * @param mixed[] $types
     */
    public function addSql(string $sql, array $params = [], array $types = []) : void
    {
        $this->versionExecutor->addSql($sql, $params, $types);
    }

    public function writeSqlFile(
        string $path,
        string $direction = self::DIRECTION_UP
    ) : bool {
        $versionExecutionResult = $this->execute($direction, true);

        if (count($versionExecutionResult->getParams()) !== 0) {
            throw MigrationNotConvertibleToSql::new($this->class);
        }

        $this->outputWriter->write("\n-- Version " . $this->version . "\n");

        $sqlQueries = [$this->version => $versionExecutionResult->getSql()];

        /*
         * Since the configuration object changes during the creation we cannot inject things
         * properly, so I had to violate LoD here (so please, let's find a way to solve it on v2).
         */
        return $this->configuration
            ->getQueryWriter()
            ->write($path, $direction, $sqlQueries);
    }

    public function execute(
        string $direction,
        bool $dryRun = false,
        bool $timeAllQueries = false
    ) : VersionExecutionResult {
        return $this->versionExecutor->execute(
            $this,
            $this->migration,
            $direction,
            $dryRun,
            $timeAllQueries
        );
    }

    public function markMigrated() : void
    {
        $this->markVersion(self::DIRECTION_UP);
    }

    public function markNotMigrated() : void
    {
        $this->markVersion(self::DIRECTION_DOWN);
    }

    public function markVersion(string $direction) : void
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
}
