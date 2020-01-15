<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration\Exception;

use Doctrine\Migrations\Configuration\Exception\ConfigurationException;
use LogicException;

final class YamlNotAvailable extends LogicException implements ConfigurationException
{
    public static function new() : self
    {
        return new self(
            'Unable to load yaml configuration files, please run '
            . '`composer require symfony/yaml` to load yaml configuration files.'
        );
    }
}
