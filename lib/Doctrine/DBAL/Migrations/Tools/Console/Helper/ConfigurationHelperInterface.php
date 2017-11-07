<?php

declare(strict_types = 1);

namespace Doctrine\DBAL\Migrations\Tools\Console\Helper;

use Doctrine\DBAL\Migrations\OutputWriter;
use Symfony\Component\Console\Input\InputInterface;

interface ConfigurationHelperInterface
{
    public function getMigrationConfig(InputInterface $input, OutputWriter $outputWriter);
}
