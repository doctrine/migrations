<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Configuration;

use Doctrine\Migrations\Configuration\Configuration;
use const PATHINFO_EXTENSION;
use function count;
use function pathinfo;

/**
 * @internal
 */
final class FormattedFile extends ConfigurationFile
{
    /** @var callable[] */
    private $loaders = [];

    private function setDefaultLoaders() : void
    {
        $this->loaders = [
            'json' => static function ($file) : ConfigurationLoader {
                return new JsonFile($file);
            },
            'php' => static function ($file) : ConfigurationLoader {
                return new PhpFile($file);
            },
            'xml' => static function ($file) : ConfigurationLoader {
                return new XmlFile($file);
            },
            'yaml' => static function ($file) : ConfigurationLoader {
                return new YamlFile($file);
            },
            'yml' => static function ($file) : ConfigurationLoader {
                return new YamlFile($file);
            },
        ];
    }

    public function getConfiguration() : Configuration
    {
        if (count($this->loaders) === 0) {
            $this->setDefaultLoaders();
        }

        $extension = pathinfo($this->file, PATHINFO_EXTENSION);
        if (! isset($this->loaders[$extension])) {
            throw Configuration\Exception\InvalidConfigurationFormat::new($this->file);
        }

        return $this->loaders[$extension]($this->file)->getConfiguration();
    }
}
