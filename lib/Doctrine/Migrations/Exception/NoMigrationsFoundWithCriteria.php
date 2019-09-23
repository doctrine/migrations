<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;

final class NoMigrationsFoundWithCriteria extends RuntimeException implements MigrationException
{
    public static function new(?string $criteria = null) : self
    {
        return new self(
            'Could not find any migrations matching your criteria.',
            4
        );
    }
}
