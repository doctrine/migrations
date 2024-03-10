<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;

use function sprintf;

final class MigrationNotExecuted extends RuntimeException implements MigrationException
{
    public static function new(string $version): self
    {
        return new self(
            sprintf(
                'The provided migration %s has not been executed',
                $version,
            ),
            5,
        );
    }
}
