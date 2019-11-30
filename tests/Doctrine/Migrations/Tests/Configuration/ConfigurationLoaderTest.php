<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Configuration;

use Doctrine\Migrations\Configuration\ConfigurationLoader;
use Doctrine\Migrations\Configuration\Exception\UnknownLoader;
use Doctrine\Migrations\Configuration\Loader\ArrayLoader;
use Doctrine\Migrations\Configuration\Loader\JsonFileLoader;
use Doctrine\Migrations\Configuration\Loader\Loader;
use Doctrine\Migrations\Configuration\Loader\PhpFileLoader;
use Doctrine\Migrations\Configuration\Loader\XmlFileLoader;
use Doctrine\Migrations\Configuration\Loader\YamlFileLoader;
use PHPUnit\Framework\TestCase;

class ConfigurationLoaderTest extends TestCase
{
    /** @var ConfigurationLoader */
    private $loader;

    public function setUp() : void
    {
        $this->loader = new ConfigurationLoader();
    }

    public function testAdd() : void
    {
        $loader = $this->createMock(Loader::class);
        $this->loader->addLoader('foo', $loader);

        self::assertSame($loader, $this->loader->getLoader('foo'));
    }

    public function testUnknownLoader() : void
    {
        $this->expectException(UnknownLoader::class);
        $this->expectExceptionMessage('Unknown configuration loader "foo".');
        $this->loader->getLoader('foo');
    }

    public function testDefaults() : void
    {
        $defaults = [
            'array' =>  ArrayLoader::class,
            'xml' =>  XmlFileLoader::class,
            'yaml' => YamlFileLoader::class,
            'yml' => YamlFileLoader::class,
            'php' => PhpFileLoader::class,
            'json' => JsonFileLoader::class,
        ];

        foreach ($defaults as $name => $class) {
            self::assertInstanceOf($class, $this->loader->getLoader($name));
        }
    }
}
