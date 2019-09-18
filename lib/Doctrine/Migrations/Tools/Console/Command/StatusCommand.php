<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tools\Console\Command;

use DateTimeImmutable;
use Doctrine\Migrations\Metadata\AvailableMigration;
use Doctrine\Migrations\Metadata\AvailableMigrationsList;
use Doctrine\Migrations\Metadata\ExecutedMigrationsSet;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function assert;
use function count;
use function is_string;
use function max;
use function sprintf;
use function str_repeat;
use function strlen;

/**
 * The StatusCommand class is responsible for outputting what the current state is of all your migrations. It shows
 * what your current version is, how many new versions you have to execute, etc. and details about each of your migrations.
 */
class StatusCommand extends AbstractCommand
{
    /** @var string */
    protected static $defaultName = 'migrations:status';

    protected function configure() : void
    {
        $this
            ->setAliases(['status'])
            ->setDescription('View the status of a set of migrations.')
            ->addOption(
                'show-versions',
                null,
                InputOption::VALUE_NONE,
                'This will display a list of all available migrations and their status'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info> command outputs the status of a set of migrations:

    <info>%command.full_name%</info>

You can output a list of all available migrations and their status with <comment>--show-versions</comment>:

    <info>%command.full_name% --show-versions</info>
EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output) : ?int
    {
        $output->writeln("\n <info>==</info> Configuration\n");

        $storage       = $this->dependencyFactory->getMetadataStorage();
        $migrationRepo = $this->dependencyFactory->getMigrationRepository();

        $availableMigrations = $migrationRepo->getMigrations();
        $executedMigrations  = $storage->getExecutedMigrations();

        $infos = $this->dependencyFactory->getMigrationStatusInfosHelper();

        foreach ($infos->getMigrationsInfos($executedMigrations, $availableMigrations) as $name => $value) {
            assert(is_string($name));

            $string = (string) $value;

            if ($name === 'New Migrations') {
                $string = $value > 0 ? '<question>' . $value . '</question>' : '0';
            }

            if ($name === 'Executed Unavailable Migrations') {
                $string = $value > 0 ? '<error>' . $value . '</error>' : '0';
            }

            $this->writeStatusInfosLineAligned($output, $name, $string);
        }

        if ($input->getOption('show-versions') === false) {
            return 0;
        }

        $executedUnavailableMigrations = $executedMigrations->getExecutedUnavailableMigrations($availableMigrations);

        if (count($availableMigrations) !== 0) {
            $output->writeln("\n <info>==</info> Available Migration Versions\n");

            $this->showVersions($availableMigrations, $executedMigrations, $output);
        }

        if (count($executedUnavailableMigrations) === 0) {
            return 0;
        }

        $output->writeln(
            "\n <info>==</info> Previously Executed Unavailable Migration Versions\n"
        );

        foreach ($executedUnavailableMigrations->getItems() as $executedUnavailableMigration) {
            $output->writeln(
                sprintf(
                    '    <comment>>></comment> <comment>%s</comment>',
                    (string) $executedUnavailableMigration->getVersion()
                )
            );
        }

        return 0;
    }

    private function writeStatusInfosLineAligned(OutputInterface $output, string $title, ?string $value) : void
    {
        $output->writeln(sprintf(
            '    <comment>>></comment> %s: %s%s',
            $title,
            str_repeat(' ', 50 - strlen($title)),
            $value
        ));
    }

    /**
     * @param AvailableMigration[] $versions
     */
    private function showVersions(
        AvailableMigrationsList $availableMigrationsSet,
        ExecutedMigrationsSet $executedMigrationsSet,
        OutputInterface $output
    ) : void {
        foreach ($availableMigrationsSet->getItems() as $availableMigration) {
            $executedMigration =  $executedMigrationsSet->hasMigration($availableMigration->getVersion()) ? $executedMigrationsSet->getMigration($availableMigration->getVersion()) : null;

            $status = $executedMigration ? '<info>migrated</info>' : '<error>not migrated</error>';

            $executedAtStatus = $executedMigration && $executedMigration->getExecutedAt() instanceof DateTimeImmutable
                ? sprintf(' (executed at %s)', $executedMigration->getExecutedAt()->format('Y-m-d H:i:s'))
                : '';

            $description = $availableMigration->getMigration()->getDescription();

            $migrationDescription = $description !== ''
                ? str_repeat(' ', 5) . $description
                : '';

            $versionName = (string) $availableMigration->getVersion();

            $output->writeln(sprintf(
                '    <comment>>></comment> <comment>%s</comment>%s%s%s%s',
                $versionName,
                str_repeat(' ', max(1, 49 - strlen($versionName))),
                $status,
                $executedAtStatus,
                $migrationDescription
            ));
        }
    }
}
