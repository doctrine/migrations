<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console;

use Doctrine\Migrations\MigratorConfiguration;
use Symfony\Component\Console\Input\InputInterface;

interface MigratorConfigurationFactory
{
    public function getMigratorConfiguration(InputInterface $input) : MigratorConfiguration;
}
