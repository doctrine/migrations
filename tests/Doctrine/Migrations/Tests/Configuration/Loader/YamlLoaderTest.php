<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\YamlNotValid;
use Doctrine\Migrations\Configuration\Loader\YamlFileLoader;

class YamlLoaderTest extends AbstractLoaderTest
{
    public function load(string $prefix = '') : Configuration
    {
        $loader = new YamlFileLoader();

        return $loader->load(__DIR__ . '/../_files/config' . ($prefix!==''? ('_' . $prefix) : '') . '.yml');
    }

    public function testMalformed() : void
    {
        $this->expectException(YamlNotValid::class);

        $this->load('malformed');
    }
}
