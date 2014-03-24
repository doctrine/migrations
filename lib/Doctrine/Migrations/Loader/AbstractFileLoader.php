<?php

namespace Doctrine\Migrations\Loader;

use Doctrine\Migrations\Loader\Loader;
use Doctrine\Migrations\Version;
use Doctrine\Migrations\MigrationInfo;
use Doctrine\Migrations\MigrationSet;

abstract class AbstractFileLoader implements Loader
{
    abstract protected function getName();

    abstract protected function getExtension();

    public function load($path)
    {
        $path = rtrim(realpath($path), '/');
        $files = glob($path . '/V*.' . $this->getExtension());
        $migrations = new MigrationSet();

        foreach ($files as $file) {
            $fileName = str_replace($path . '/', '', $file);

            if (preg_match('(^V([0-9\.]+)+(_([^\.]+))?\.' . $this->getExtension() . '$)', $fileName, $matches)) {
                $migrations->add(
                    new MigrationInfo(
                        new Version($matches[1]),
                        str_replace('_', ' ', $matches[3]),
                        $this->getName(),
                        $file,
                        md5_file($file)
                    )
                );
            } else {
                throw new \RuntimeException(sprintf(
                    "Invalid %s migration file format '%s'. Expected V[0-9]+.%s or V[0-9]+_[^\.].%s",
                    $this->getName(),
                    $fileName,
                    $this->getExtension(),
                    $this->getExtension()
                ));
            }
        }

        return $migrations;
    }
}
