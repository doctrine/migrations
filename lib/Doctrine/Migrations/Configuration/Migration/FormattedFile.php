<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Migration\Exception\InvalidConfigurationFormat;

use function count;
use function pathinfo;

use const PATHINFO_EXTENSION;

/**
 * @internal
 */
final class FormattedFile extends ConfigurationFile
{
    /** @var callable[] */
    private array $loaders = [];

    private function setDefaultLoaders(): void
    {
        $this->loaders = [
            'json' => static function ($file): ConfigurationLoader {
                return new JsonFile($file);
            },
            'php' => static function ($file): ConfigurationLoader {
                return new PhpFile($file);
            },
            'xml' => static function ($file): ConfigurationLoader {
                return new XmlFile($file);
            },
            'yaml' => static function ($file): ConfigurationLoader {
                return new YamlFile($file);
            },
            'yml' => static function ($file): ConfigurationLoader {
                return new YamlFile($file);
            },
        ];
    }

    public function getConfiguration(): Configuration
    {
        if (count($this->loaders) === 0) {
            $this->setDefaultLoaders();
        }

        $extension = pathinfo($this->file, PATHINFO_EXTENSION);
        if (! isset($this->loaders[$extension])) {
            throw InvalidConfigurationFormat::new($this->file);
        }

        return $this->loaders[$extension]($this->file)->getConfiguration();
    }
}
