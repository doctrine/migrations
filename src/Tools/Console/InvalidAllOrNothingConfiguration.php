<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console;

use Doctrine\Migrations\Configuration\Exception\ConfigurationException;
use LogicException;

final class InvalidAllOrNothingConfiguration extends LogicException implements ConfigurationException
{
    public static function new(): self
    {
        return new self('Providing --all-or-nothing and --no-all-or-nothing simultaneously is forbidden.');
    }
}
