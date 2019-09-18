<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;
use function sprintf;

final class MigrationNotAvailable extends RuntimeException implements MigrationException
{
    public static function new(string $version) : self
    {
        return new self(
            sprintf(
                'The migration %s is not available',
                $version
            ),
            5
        );
    }
}
