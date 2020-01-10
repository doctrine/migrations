<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Configuration\Exception;

use Doctrine\Migrations\Configuration\Exception\ConfigurationException;
use LogicException;

final class InvalidConfigurationFormat extends LogicException implements ConfigurationException
{
    public static function new() : self
    {
        return new self('The provided configuration file can not be parsed.');
    }
}
