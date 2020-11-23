<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;

final class MetadataStorageError extends RuntimeException implements MigrationException
{
    public static function notUpToDate(): self
    {
        return new self('The metadata storage is not up to date, please run the sync-metadata-storage command to fix this issue.');
    }

    public static function notInitialized(): self
    {
        return new self('The metadata storage is not initialized, please run the sync-metadata-storage command to fix this issue.');
    }
}
