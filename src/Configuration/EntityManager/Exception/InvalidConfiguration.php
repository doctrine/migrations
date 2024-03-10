<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\EntityManager\Exception;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

final class InvalidConfiguration extends InvalidArgumentException implements LoaderException
{
    public static function invalidArrayConfiguration(): self
    {
        return new self('The EntityManager file has to return an array with database configuration parameters.');
    }

    public static function invalidManagerType(object $em): self
    {
        return new self(sprintf(
            'The returned manager must implement %s, %s returned.',
            EntityManagerInterface::class,
            get_debug_type($em),
        ));
    }
}
