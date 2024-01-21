<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use function dirname;
use function realpath;

abstract class ConfigurationFile implements ConfigurationLoader
{
    /** @var string */
    protected $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * @param array<string,string> $directories
     *
     * @return array<string,string>
     */
    final protected function getDirectoriesRelativeToFile(array $directories, string $file): array
    {
        foreach ($directories as $ns => $dir) {
            $path = realpath(dirname($file) . '/' . $dir);

            $directories[$ns] = $path !== false ? $path : $dir;
        }

        return $directories;
    }
}
