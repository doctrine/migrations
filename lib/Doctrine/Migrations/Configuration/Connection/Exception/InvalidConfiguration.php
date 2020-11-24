<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Connection\Exception;

use Doctrine\DBAL\Connection;
use InvalidArgumentException;

use function get_class;
use function sprintf;

final class InvalidConfiguration extends InvalidArgumentException implements LoaderException
{
    public static function invalidArrayConfiguration(): self
    {
        return new self('The connection file has to return an array with database configuration parameters.');
    }

    public static function invalidConnectionType(object $connection): self
    {
        return new self(sprintf('The returned connection must be a %s instance, %s returned.', Connection::class, get_class($connection)));
    }
}
