<?php

namespace Doctrine\Migrations\DBAL\Loader;

use Doctrine\Migrations\Loader\AbstractFileLoader;

class PhpFileLoader implements Loader
{
    protected function getName()
    {
        return 'PHP';
    }

    protected function getExtension()
    {
        return 'php';
    }
}
