<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Exception;

use LogicException;
use function sprintf;

final class MissingNamespaceConfiguration extends LogicException implements ConfigurationException
{
    public static function new() : self
    {
        return new self(sprintf('There are no namespaces configured.'), 10);
    }
}
