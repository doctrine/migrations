<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration;

use Doctrine\Migrations\Configuration\Exception\UnknownLoader;
use Doctrine\Migrations\Configuration\Loader\ArrayLoader;
use Doctrine\Migrations\Configuration\Loader\JsonFileLoader;
use Doctrine\Migrations\Configuration\Loader\Loader;
use Doctrine\Migrations\Configuration\Loader\PhpFileLoader;
use Doctrine\Migrations\Configuration\Loader\XmlFileLoader;
use Doctrine\Migrations\Configuration\Loader\YamlFileLoader;
use function count;

/**
 * @internal
 */
class ConfigurationLoader
{
    /** @var Loader[] */
    private $loaders = [];

    public function addLoader(string $type, Loader $loader) : void
    {
        $this->loaders[$type] = $loader;
    }

    private function setDefaultLoaders() : void
    {
        $this->loaders = [
            'array' => new ArrayLoader(),
            'json' => new JsonFileLoader(),
            'php' => new PhpFileLoader(),
            'xml' => new XmlFileLoader(),
            'yaml' => new YamlFileLoader(),
            'yml' => new YamlFileLoader(),
        ];
    }

    public function getLoader(string $type) : Loader
    {
        if (count($this->loaders) === 0) {
            $this->setDefaultLoaders();
        }

        if (! isset($this->loaders[$type])) {
            throw UnknownLoader::new($type);
        }

        return $this->loaders[$type];
    }
}
