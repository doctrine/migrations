<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration\Loader;

use Doctrine\Migrations\Configuration\AbstractFileConfiguration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Exception\XmlNotValid;
use Doctrine\Migrations\Configuration\Loader\Loader;
use Doctrine\Migrations\Configuration\Loader\XmlFileLoader;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Finder\GlobFinder;
use Doctrine\Migrations\Finder\MigrationFinder;
use Doctrine\Migrations\OutputWriter;
use Doctrine\Migrations\Tests\MigrationTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use const DIRECTORY_SEPARATOR;

class XmlLoaderTest extends AbstractLoaderTest
{
    public function getLoader(): Loader
    {
        return new XmlFileLoader();
    }

    public function getExtension(): string
    {
        return 'xml';
    }

    public function testMalformed() : void
    {
        $this->expectException(XmlNotValid::class);

        $loader = $this->getLoader();
        $loader->load(__DIR__ ."/../_files/config_malformed.".$this->getExtension());
    }

}
