<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools;

use function strtolower;

/**
 * @internal
 */
class BooleanStringFormatter
{
    public static function toBoolean(string $value, bool $default) : bool
    {
        switch (strtolower($value)) {
            case 'true':
                return true;

            case '1':
                return true;

            case 'false':
                return false;

            case '0':
                return false;

            default:
                return $default;
        }
    }
}
