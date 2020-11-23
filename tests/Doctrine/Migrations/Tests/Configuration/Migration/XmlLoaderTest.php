<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Migration\Exception\XmlNotValid;
use Doctrine\Migrations\Configuration\Migration\XmlFile;

class XmlLoaderTest extends LoaderTest
{
    public function load(string $prefix = ''): Configuration
    {
        $loader = new XmlFile(__DIR__ . '/../_files/config' . ($prefix !== '' ? '_' . $prefix : '') . '.xml');

        return $loader->getConfiguration();
    }

    public function testConfigurationWithInvalidOption(): void
    {
        $this->expectException(XmlNotValid::class);

        $this->load('invalid');
    }

    public function testMalformed(): void
    {
        $this->expectException(XmlNotValid::class);

        $this->load('malformed');
    }
}
