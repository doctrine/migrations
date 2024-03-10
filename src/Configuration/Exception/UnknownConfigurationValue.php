<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Exception;

use LogicException;

use function sprintf;
use function var_export;

final class UnknownConfigurationValue extends LogicException implements ConfigurationException
{
    public static function new(string $key, mixed $value): self
    {
        return new self(
            sprintf(
                'Unknown %s for configuration "%s".',
                var_export($value, true),
                $key,
            ),
            10,
        );
    }
}
