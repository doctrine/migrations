<?php

namespace Doctrine\Migrations\DBAL\Loader;

use Doctrine\Migrations\Loader\Loader;
use Doctrine\Migrations\Version;
use Doctrine\Migrations\MigrationInfo;
use Doctrine\Migrations\MigrationSet;

class SqlFileLoader implements Loader
{
    public function load($path)
    {
        $path = rtrim(realpath($path), '/');
        $files = glob($path . '/V*.sql');
        $migrations = new MigrationSet();

        foreach ($files as $file) {
            $fileName = str_replace($path . '/', '', $file);

            if (preg_match('(V([0-9\.]+)+(_([^\.]+))?\.sql)', $fileName, $matches)) {
                $migrations->add(
                    new MigrationInfo(
                        new Version($matches[1]),
                        $matches[3],
                        'sql',
                        $file,
                        md5_file($file)
                    )
                );
            }
        }

        return $migrations;
    }
}
