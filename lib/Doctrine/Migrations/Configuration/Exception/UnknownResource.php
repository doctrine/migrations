<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Exception;

use LogicException;
use function sprintf;

final class UnknownResource extends LogicException implements ConfigurationException
{
    public static function new(string $loader) : self
    {
        return new self(sprintf('The provided resource can not be loaded by the loader "%s".', $loader), 10);
    }
}
