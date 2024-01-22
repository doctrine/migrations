<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Exception;

use InvalidArgumentException;

use function sprintf;

final class FileNotFound extends InvalidArgumentException implements ConfigurationException
{
    public static function new(string $file): self
    {
        return new self(sprintf('The "%s" configuration file does not exist.', $file));
    }
}
