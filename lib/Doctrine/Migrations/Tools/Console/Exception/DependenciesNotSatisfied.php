<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Exception;

use LogicException;

final class DependenciesNotSatisfied extends LogicException implements ConsoleException
{
    public static function new() : self
    {
        return new self('The dependency factory has not been initialized or provided.');
    }
}
