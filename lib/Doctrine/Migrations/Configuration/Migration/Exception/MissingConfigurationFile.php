<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration\Exception;

use Doctrine\Migrations\Configuration\Exception\ConfigurationException;
use LogicException;

final class MissingConfigurationFile extends LogicException implements ConfigurationException
{
    public static function new() : self
    {
        return new self('It was not possible to locate any configuration file.');
    }
}
