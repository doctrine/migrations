<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools;

use function strtolower;

/**
 * The BooleanStringFormatter class is responsible for formatting a string boolean representation to a PHP boolean value.
 * It is used in the XmlConfiguration class to convert the string XML boolean value to a PHP boolean value.
 *
 * @internal
 *
 * @see Doctrine\Migrations\Configuration\XmlConfiguration
 */
class BooleanStringFormatter
{
    public static function toBoolean(string $value, bool $default): bool
    {
        return match (strtolower($value)) {
            'true', '1' => true,
            'false', '0' => false,
            default => $default,
        };
    }
}
