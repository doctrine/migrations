<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\XmlNotValid;
use Doctrine\Migrations\Configuration\Loader\XmlFileLoader;

class XmlLoaderTest extends AbstractLoaderTest
{
    public function load(string $prefix = '') : Configuration
    {
        $loader = new XmlFileLoader();

        return $loader->load(__DIR__ . '/../_files/config' . ($prefix!==''? ('_' . $prefix) : '') . '.xml');
    }

    public function testConfigurationWithInvalidOption() : void
    {
        $this->expectException(XmlNotValid::class);

        $this->load('invalid');
    }

    public function testMalformed() : void
    {
        $this->expectException(XmlNotValid::class);

        $this->load('malformed');
    }
}
