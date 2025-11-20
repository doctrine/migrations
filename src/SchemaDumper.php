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
use function method_exists;
use function preg_last_error;
use function preg_last_error_msg;
use function preg_match;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;

use const PREG_INTERNAL_ERROR;

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
    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     * @param string[]                                $excludedTablesRegexes
     */
    public function __construct(
        private readonly AbstractPlatform $platform,
        private readonly AbstractSchemaManager $schemaManager,
        private readonly Generator $migrationGenerator,
        private readonly SqlGenerator $migrationSqlGenerator,
        private readonly array $excludedTablesRegexes = [],
    ) {
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
        bool|null $nowdocOutput = null,
        int $lineLength = 120,
    ): string {
        $schema = $this->schemaManager->introspectSchema();

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
                $nowdocOutput,
                $lineLength,
            );

            if ($upCode !== '') {
                $up[] = $upCode;
            }

            if (method_exists($table, 'getObjectName')) {
                $tableName = $table->getObjectName()->toSQL($this->platform);
            } else {
                $tableName = $table->getQuotedName($this->platform);
            }

            $downSql  = [$this->platform->getDropTableSQL($tableName)];
            $downCode = $this->migrationSqlGenerator->generate(
                $downSql,
                $formatted,
                $nowdocOutput,
                $lineLength,
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
            $down,
        );
    }

    /** @param string[] $excludedTablesRegexes */
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
     * @param mixed[]                                                 $matches
     * @param int-mask-of<PREG_OFFSET_CAPTURE|PREG_UNMATCHED_AS_NULL> $flags
     *
     * @phpstan-ignore parameterByRef.unusedType
     */
    private static function pregMatch(string $pattern, string $subject, array|null &$matches = null, int $flags = 0, int $offset = 0): int
    {
        $errorMessages = [];
        set_error_handler(static function (int $severity, string $message) use (&$errorMessages): bool {
            $errorMessages[] = $message;

            return true;
        });

        try {
            $ret = preg_match($pattern, $subject, $matches, $flags, $offset);
        } finally {
            restore_error_handler();
        }

        if ($ret === false) {
            throw new InvalidArgumentException(match (preg_last_error()) {
                PREG_INTERNAL_ERROR => sprintf('Internal PCRE error, please check your Regex. Reported errors: %s.', implode(', ', $errorMessages)),
                default => preg_last_error_msg(),
            });
        }

        return $ret;
    }
}
