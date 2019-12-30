<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use Doctrine\Migrations\Version\Version;
use RuntimeException;
use function sprintf;
use function strval;

final class RolldownFailed extends RuntimeException implements MigrationException
{
    public static function migrationNotExecuted(Version $version) : self
    {
        return new self(
            sprintf(
                'The provided migration %s was not executed and can therefore not be rolled back.',
                strval($version)
            ),
            5
        );
    }
}
