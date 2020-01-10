<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Configuration;

use Doctrine\Migrations\Configuration\Configuration;

interface ConfigurationLoader
{
    public function getConfiguration() : Configuration;
}
