<?php

namespace Doctrine\DBAL\Migrations;

class MigrationsVersion
{
    /** @var string */
    private static $version = 'v1.6.2';

    public static function VERSION()
    {
        $gitversion = '@git-version@';

        if (self::isACustomPharBuild($gitversion)) {
            return $gitversion;
        }
        return self::$version;
    }

    /**
     * Check if doctrine migration is installed by composer or
     * in a modified (not tagged) phar version.
     *
     * @param string $gitversion
     *
     * @return bool
     */
    private static function isACustomPharBuild($gitversion)
    {
        return $gitversion !== '@' . 'git-version@';
    }
}
