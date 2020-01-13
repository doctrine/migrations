<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Exception;

use RuntimeException;

final class MissingDependency extends RuntimeException implements DependencyException
{
    public static function noEntityManager() : self
    {
        return new self('The entity manager is not available.');
    }
}
