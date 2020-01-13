<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Configuration\Exception;

use Doctrine\Migrations\Configuration\Exception\ConfigurationException;
use LogicException;

final class JsonNotValid extends LogicException implements ConfigurationException
{
    public static function new() : self
    {
        return new self('Configuration is not valid JSON.', 10);
    }
}
