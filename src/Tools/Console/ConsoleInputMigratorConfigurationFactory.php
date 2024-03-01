<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console;

use Doctrine\Deprecations\Deprecation;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\MigratorConfiguration;
use Symfony\Component\Console\Input\InputInterface;

class ConsoleInputMigratorConfigurationFactory implements MigratorConfigurationFactory
{
    public const ABSENT_CONFIG_VALUE = 'notprovided';

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

        if ($wasOptionExplicitlyPassed && ($allOrNothingOption !== null && $allOrNothingOption !== self::ABSENT_CONFIG_VALUE)) {
            Deprecation::trigger(
                'doctrine/migrations',
                'https://github.com/doctrine/migrations/issues/1304',
                <<<'DEPRECATION'
                    Context: Passing values to option `--all-or-nothing`
                    Problem: Passing values is deprecated
                    Solution: If you need to disable the behavior, omit the option,
                    otherwise, pass the option without a value
                    DEPRECATION,
            );
        }

        return match ($allOrNothingOption) {
            self::ABSENT_CONFIG_VALUE => null,
            null => false,
            default => (bool) $allOrNothingOption,
        };
    }
}
