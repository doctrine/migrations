<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Loader;

use Doctrine\Migrations\Configuration\Configuration;

interface Loader
{
    /**
     * @param mixed $resource
     */
    function load($resource) : Configuration;
}
