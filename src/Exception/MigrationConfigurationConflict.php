<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use Doctrine\Migrations\AbstractMigration;
use UnexpectedValueException;

use function get_debug_type;
use function sprintf;

final class MigrationConfigurationConflict extends UnexpectedValueException implements MigrationException
{
    public static function migrationIsNotTransactional(AbstractMigration $migration): self
    {
        return new self(sprintf(
            <<<'EXCEPTION'
                Context: attempting to execute migrations with all-or-nothing enabled
                Problem: migration %s is marked as non-transactional
                Solution: disable all-or-nothing in configuration or by command-line option, or enable transactions for all migrations
                EXCEPTION,
            get_debug_type($migration),
        ));
    }
}
