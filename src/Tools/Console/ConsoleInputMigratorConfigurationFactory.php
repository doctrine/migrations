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
        $enableAllOrNothingOption  = self::ABSENT_CONFIG_VALUE;
        $disableAllOrNothingOption = null;

        if ($input->hasOption('no-all-or-nothing')) {
            $disableAllOrNothingOption = $input->getOption('no-all-or-nothing');
        }

        $wasOptionExplicitlyPassed = $input->hasOption('all-or-nothing');

        if ($wasOptionExplicitlyPassed) {
            /**
             * Due to this option being able to receive optional values, its behavior is tricky:
             * - when `--all-or-nothing` option is not provided, the default is set to self::ABSENT_CONFIG_VALUE
             * - when `--all-or-nothing` option is provided without values, this will be `null`
             * - when `--all-or-nothing` option is provided with a value, we get the provided value
             */
            $enableAllOrNothingOption = $input->getOption('all-or-nothing');
        }

        $enableAllOrNothingDeprecation = match ($enableAllOrNothingOption) {
            self::ABSENT_CONFIG_VALUE, null => false,
            default => true,
        };

        if ($enableAllOrNothingOption !== self::ABSENT_CONFIG_VALUE && $disableAllOrNothingOption === true) {
            throw InvalidAllOrNothingConfiguration::new();
        }

        if ($disableAllOrNothingOption === true) {
            return false;
        }

        if ($enableAllOrNothingDeprecation) {
            Deprecation::trigger(
                'doctrine/migrations',
                'https://github.com/doctrine/migrations/issues/1304',
                <<<'DEPRECATION'
                    Context: Passing values to option `--all-or-nothing`
                    Problem: Passing values is deprecated
                    Solution: If you need to disable the behavior, use --no-all-or-nothing,
                    otherwise, pass the option without a value
                    DEPRECATION,
            );
        }

        return match ($enableAllOrNothingOption) {
            self::ABSENT_CONFIG_VALUE => null,
            null => true,
            default => (bool) $enableAllOrNothingOption,
        };
    }
}
