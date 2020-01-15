<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration\Exception;

use Doctrine\Migrations\Configuration\Exception\ConfigurationException;
use LogicException;
use function sprintf;

final class InvalidConfigurationFormat extends LogicException implements ConfigurationException
{
    public static function new(string $file) : self
    {
        return new self(sprintf('Configuration file "%s" cannot be parsed.', $file));
    }
}
