<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Exception;

use LogicException;
use function sprintf;

final class UnknownLoader extends LogicException implements ConfigurationException
{
    public static function new(string $loader) : self
    {
        return new self(sprintf('Unknown configuration loader "%s".', $loader), 10);
    }
}
