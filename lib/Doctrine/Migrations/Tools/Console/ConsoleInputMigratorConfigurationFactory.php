<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console;

use Doctrine\Deprecations\Deprecation;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\MigratorConfiguration;
use Symfony\Component\Console\Input\InputInterface;

class ConsoleInputMigratorConfigurationFactory implements MigratorConfigurationFactory
{
    public function __construct(private readonly Configuration $configuration)
    {
    }

    public function getMigratorConfiguration(InputInterface $input): MigratorConfiguration
    {
        $timeAllQueries = $input->hasOption('query-time') ? (bool) $input->getOption('query-time') : false;
        $dryRun         = $input->hasOption('dry-run') ? (bool) $input->getOption('dry-run') : false;
        $allOrNothing   = $this->determineAllOrNothingValueFrom($input) ?? $this->configuration->isAllOrNothing();

        return (new MigratorConfiguration())
            ->setDryRun($dryRun)
            ->setTimeAllQueries($timeAllQueries)
            ->setAllOrNothing($allOrNothing);
    }

    private function determineAllOrNothingValueFrom(InputInterface $input): bool|null
    {
        $allOrNothingOption        = null;
        $wasOptionExplicitlyPassed = $input->hasOption('all-or-nothing');

        if ($wasOptionExplicitlyPassed) {
            $allOrNothingOption = $input->getOption('all-or-nothing');
        }

        if ($wasOptionExplicitlyPassed && $allOrNothingOption !== null) {
            Deprecation::trigger(
                'doctrine/migrations',
                'https://github.com/doctrine/migrations/issues/1304',
                <<<'DEPRECATION'
                    Context: Passing values to option `--all-or-nothing`
                    Problem: Passing values is deprecated
                    Solution: From version 4.0.x, `--all-or-nothing` option won't accept any value, 
                    and the presence of the option will be treated as `true`.
                    DEPRECATION,
            );
        }

        if ($allOrNothingOption === 'notprovided') {
            return null;
        }

        return (bool) ($allOrNothingOption ?? false);
    }
}
