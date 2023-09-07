<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;
use Throwable;

final class NoMigrationsToExecute extends RuntimeException implements MigrationException
{
    public static function new(Throwable|null $previous = null): self
    {
        return new self(
            'Could not find any migrations to execute.',
            4,
            $previous,
        );
    }
}
