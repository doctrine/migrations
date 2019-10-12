<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use Doctrine\Migrations\Version\Version;
use RuntimeException;
use function sprintf;

final class MigrationNotAvailable extends RuntimeException implements MigrationException
{
    public static function forVersion(Version $version) : self
    {
        return new self(
            sprintf(
                'The migration %s is not available',
                (string) $version
            ),
            5
        );
    }
}
