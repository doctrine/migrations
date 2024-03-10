<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Configuration\Migration;

use Doctrine\Migrations\Configuration\Configuration;

interface ConfigurationLoader
{
    public function getConfiguration(): Configuration;
}
