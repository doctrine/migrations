<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration\Exception;

use Doctrine\Migrations\Configuration\Exception\ConfigurationException;
use LogicException;

final class YamlNotValid extends LogicException implements ConfigurationException
{
    public static function malformed(): self
    {
        return new self('The YAML configuration is malformed.');
    }

    public static function invalid(): self
    {
        return new self('Configuration is not valid YAML.', 10);
    }
}
