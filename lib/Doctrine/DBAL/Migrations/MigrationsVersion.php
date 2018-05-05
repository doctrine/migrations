<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations;

class MigrationsVersion
{
    /** @var string */
    private static $version = 'v2.0.0';

    public static function VERSION() : string
    {
        $gitVersion = '@git-version@';

        if (self::isACustomPharBuild($gitVersion)) {
            return $gitVersion;
        }

        return self::$version;
    }

    private static function isACustomPharBuild(string $gitVersion) : bool
    {
        return $gitVersion !== '@' . 'git-version@';
    }
}
