<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use LogicException;

final class FrozenDependencies extends LogicException implements DependencyException
{
    public static function new() : self
    {
        return new self('The dependencies are frozen and cannot be edited anymore.');
    }
}
