<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Configuration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Configuration\XmlFile;

class XmlLoaderTest extends LoaderTest
{
    public function load(string $prefix = '') : Configuration
    {
        $loader = new XmlFile(__DIR__ . '/../_files/config' . ($prefix!==''? ('_' . $prefix) : '') . '.xml');

        return $loader->getConfiguration();
    }

    public function testConfigurationWithInvalidOption() : void
    {
        $this->expectException(Configuration\Exception\XmlNotValid::class);

        $this->load('invalid');
    }

    public function testMalformed() : void
    {
        $this->expectException(Configuration\Exception\XmlNotValid::class);

        $this->load('malformed');
    }
}
