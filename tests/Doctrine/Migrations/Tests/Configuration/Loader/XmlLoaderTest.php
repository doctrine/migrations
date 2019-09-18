<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\XmlNotValid;
use Doctrine\Migrations\Configuration\Loader\XmlFileLoader;

class XmlLoaderTest extends AbstractLoaderTest
{
    public function load($prefix = '') : Configuration
    {
        $loader = new XmlFileLoader();

        return $loader->load(__DIR__ . '/../_files/config' . ($prefix? ('_' . $prefix) : '') . '.xml');
    }

    public function testMalformed() : void
    {
        $this->expectException(XmlNotValid::class);

        $this->load('malformed');
    }
}
