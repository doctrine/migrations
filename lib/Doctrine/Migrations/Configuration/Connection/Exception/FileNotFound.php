<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection\Exception;

use InvalidArgumentException;
use function sprintf;

final class FileNotFound extends InvalidArgumentException implements LoaderException
{
    public static function new(string $file) : self
    {
        return new self(sprintf('Database configuration file "%s" does not exist.', $file));
    }
}
