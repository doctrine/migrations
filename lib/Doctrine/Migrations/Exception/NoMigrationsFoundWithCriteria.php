<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;

use function sprintf;

final class NoMigrationsFoundWithCriteria extends RuntimeException implements MigrationException
{
    public static function new(?string $criteria = null): self
    {
        return new self(
            $criteria !== null
                ? sprintf('Could not find any migrations matching your criteria (%s).', $criteria)
                : 'Could not find any migrations matching your criteria.',
            4
        );
    }
}
