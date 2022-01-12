<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\Migrations\Exception\NoTablesFound;
use Doctrine\Migrations\Generator\Generator;
use Doctrine\Migrations\Generator\SqlGenerator;
use InvalidArgumentException;

use function array_merge;
use function count;
use function implode;
use function preg_last_error;
use function preg_match;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;

use const PREG_BACKTRACK_LIMIT_ERROR;
use const PREG_BAD_UTF8_ERROR;
use const PREG_BAD_UTF8_OFFSET_ERROR;
use const PREG_INTERNAL_ERROR;
use const PREG_RECURSION_LIMIT_ERROR;

/**
 * The SchemaDumper class is responsible for dumping the current state of your database schema to a migration. This
 * is to be used in conjunction with the Rollup class.
 *
 * @internal
 *
 * @see Doctrine\Migrations\Rollup
 */
class SchemaDumper
{
    /** @var AbstractPlatform */
    private $platform;

    /** @var AbstractSchemaManager<AbstractPlatform> */
    private $schemaManager;

    /** @var Generator */
    private $migrationGenerator;

    /** @var SqlGenerator */
    private $migrationSqlGenerator;

    /** @var string[] */
    private $excludedTablesRegexes;

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     * @param string[]                                $excludedTablesRegexes
     */
    public function __construct(
        AbstractPlatform $platform,
        AbstractSchemaManager $schemaManager,
        Generator $migrationGenerator,
        SqlGenerator $migrationSqlGenerator,
        array $excludedTablesRegexes = []
    ) {
        $this->platform              = $platform;
        $this->schemaManager         = $schemaManager;
        $this->migrationGenerator    = $migrationGenerator;
        $this->migrationSqlGenerator = $migrationSqlGenerator;
        $this->excludedTablesRegexes = $excludedTablesRegexes;
    }

    /**
     * @param string[] $excludedTablesRegexes
     *
     * @throws NoTablesFound
     */
    public function dump(
        string $fqcn,
        array $excludedTablesRegexes = [],
        bool $formatted = false,
        int $lineLength = 120
    ): string {
        $schema = $this->schemaManager->createSchema();

        $up   = [];
        $down = [];

        foreach ($schema->getTables() as $table) {
            if ($this->shouldSkipTable($table, $excludedTablesRegexes)) {
                continue;
            }

            $upSql = $this->platform->getCreateTableSQL($table);

            $upCode = $this->migrationSqlGenerator->generate(
                $upSql,
                $formatted,
                $lineLength
            );

            if ($upCode !== '') {
                $up[] = $upCode;
            }

            $downSql = [$this->platform->getDropTableSQL($table)];

            $downCode = $this->migrationSqlGenerator->generate(
                $downSql,
                $formatted,
                $lineLength
            );

            if ($downCode === '') {
                continue;
            }

            $down[] = $downCode;
        }

        if (count($up) === 0) {
            throw NoTablesFound::new();
        }

        $up   = implode("\n", $up);
        $down = implode("\n", $down);

        return $this->migrationGenerator->generateMigration(
            $fqcn,
            $up,
            $down
        );
    }

    /**
     * @param string[] $excludedTablesRegexes
     */
    private function shouldSkipTable(Table $table, array $excludedTablesRegexes): bool
    {
        foreach (array_merge($excludedTablesRegexes, $this->excludedTablesRegexes) as $regex) {
            if (self::pregMatch($regex, $table->getName()) !== 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * A local wrapper for "preg_match" which will throw a InvalidArgumentException if there
     * is an internal error in the PCRE engine.
     * Copied from https://github.com/symfony/symfony/blob/62216ea67762b18982ca3db73c391b0748a49d49/src/Symfony/Component/Yaml/Parser.php#L1072-L1090
     *
     * @internal
     *
     * @param mixed[] $matches
     */
    private static function pregMatch(string $pattern, string $subject, ?array &$matches = null, int $flags = 0, int $offset = 0): int
    {
        try {
            $errorMessages = [];
            set_error_handler(static function (int $severity, string $message, string $file, int $line) use (&$errorMessages): bool {
                $errorMessages[] = $message;

                return true;
            });
            $ret = preg_match($pattern, $subject, $matches, $flags, $offset);
        } finally {
            restore_error_handler();
        }

        if ($ret === false) {
            switch (preg_last_error()) {
                case PREG_INTERNAL_ERROR:
                    $error = sprintf('Internal PCRE error, please check your Regex. Reported errors: %s.', implode(', ', $errorMessages));
                    break;
                case PREG_BACKTRACK_LIMIT_ERROR:
                    $error = 'pcre.backtrack_limit reached.';
                    break;
                case PREG_RECURSION_LIMIT_ERROR:
                    $error = 'pcre.recursion_limit reached.';
                    break;
                case PREG_BAD_UTF8_ERROR:
                    $error = 'Malformed UTF-8 data.';
                    break;
                case PREG_BAD_UTF8_OFFSET_ERROR:
                    $error = 'Offset doesn\'t correspond to the begin of a valid UTF-8 code point.';
                    break;
                default:
                    $error = 'Error.';
            }

            throw new InvalidArgumentException($error);
        }

        return $ret;
    }
}
