<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Exception;

use LogicException;

final class FrozenConfiguration extends LogicException implements ConfigurationException
{
    public static function new(): self
    {
        return new self('The configuration is frozen and cannot be edited anymore.');
    }
}
