<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console;

use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\MigratorConfiguration;
use Symfony\Component\Console\Input\InputInterface;

class ConsoleInputMigratorConfigurationFactory implements MigratorConfigurationFactory
{
    /** @var Configuration */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getMigratorConfiguration(InputInterface $input) : MigratorConfiguration
    {
        $timeAllQueries = $input->hasOption('query-time') ? (bool) $input->getOption('query-time') : false;
        $dryRun         = $input->hasOption('dry-run') ? (bool) $input->getOption('dry-run') : false;
        $allOrNothing   = $input->hasOption('all-or-nothing') ? (bool) $input->getOption('all-or-nothing') : $this->configuration->isAllOrNothing();

        return (new MigratorConfiguration())
            ->setDryRun($dryRun)
            ->setTimeAllQueries($timeAllQueries)
            ->setAllOrNothing($allOrNothing);
    }
}
