<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection\Exception;

use InvalidArgumentException;

final class ConnectionNotSpecified extends InvalidArgumentException implements LoaderException
{
    public static function new() : self
    {
        return new self(
            'You have to specify a --db-configuration file or pass a Database Connection as a dependency to the Migrations.'
        );
    }
}
