<?php

namespace Doctrine\Migrations\DBAL\Loader;

use Doctrine\Migrations\Loader\AbstractFileLoader;

class PhpFileLoader extends AbstractFileLoader
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
