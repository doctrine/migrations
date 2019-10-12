<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Exception;

use LogicException;
use function sprintf;

final class UnableToLoadResource extends LogicException implements ConfigurationException
{
    public static function with(string $loader) : self
    {
        return new self(sprintf('The provided resource can not be loaded by the loader "%s".', $loader), 10);
    }
}
