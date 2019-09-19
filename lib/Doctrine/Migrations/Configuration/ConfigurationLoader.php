<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Migrations\Configuration\Exception\MigrationsNamespaceRequired;
use Doctrine\Migrations\Configuration\Exception\UnknownConfigurationValue;
use Doctrine\Migrations\Configuration\Exception\UnknownLoader;
use Doctrine\Migrations\Configuration\Loader\ArrayLoader;
use Doctrine\Migrations\Configuration\Loader\JsonFileLoader;
use Doctrine\Migrations\Configuration\Loader\Loader;
use Doctrine\Migrations\Configuration\Loader\PHPFileLoader;
use Doctrine\Migrations\Configuration\Loader\XmlFileLoader;
use Doctrine\Migrations\Configuration\Loader\YamlFileLoader;
use Doctrine\Migrations\Exception\MigrationException;
use Doctrine\Migrations\Metadata\Storage\MetadataStorageConfigration;
use Doctrine\Migrations\Tools\Console\Exception\FileTypeNotSupported;
use Symfony\Component\Console\Input\InputInterface;
use function strcasecmp;

class ConfigurationLoader
{
    /**
     * @var Loader[]
     */
    private $loaders = [];


    public function addLoader(string $type, Loader $loader)
    {
        $this->loaders[$type] = $loader;
    }

    private function setDefaultLoaders()
    {
        $this->loaders = [
            'array' => new ArrayLoader(),
            'xml' => new XmlFileLoader(),
            'yaml' => new YamlFileLoader(),
            'yml' => new YamlFileLoader(),
            'php' => new PHPFileLoader(),
            'json' => new JsonFileLoader(),
        ];
    }

    public function getLoader($type): Loader
    {
        if (count($this->loaders) === 0) {
            $this->setDefaultLoaders();
        }

        if (!isset($this->loaders[$type])) {
            throw UnknownLoader::new($type);
        }

        return $this->loaders[$type];
    }
}
