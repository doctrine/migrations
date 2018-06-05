<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Finder\Exception;

use InvalidArgumentException;
use const PHP_EOL;
use function sprintf;

final class NameIsReserved extends InvalidArgumentException implements FinderException
{
    public static function new(string $version) : self
    {
        return new self(sprintf(
            'Cannot load a migrations with the name "%s" because it is a reserved number by Doctrine Migrations.'
            . PHP_EOL
            . "It's used to revert all migrations including the first one.",
            $version
        ));
    }
}
