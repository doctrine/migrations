<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;

use function sprintf;

final class MigrationClassNotFound extends RuntimeException implements MigrationException
{
    public static function new(string $migrationClass): self
    {
        return new self(
            sprintf(
                'Migration class "%s" was not found?',
                $migrationClass,
            ),
        );
    }
}
