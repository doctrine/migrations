<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Migrations\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Doctrine\DBAL\Migrations\Version;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function count;
use function in_array;
use function max;
use function sprintf;
use function str_repeat;
use function strlen;

class StatusCommand extends AbstractCommand
{
    protected function configure() : void
    {
        $this
            ->setName('migrations:status')
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

    public function execute(InputInterface $input, OutputInterface $output) : void
    {
        $configuration = $this->getMigrationConfiguration($input, $output);

        $infos = new MigrationStatusInfosHelper($configuration);

        $output->writeln("\n <info>==</info> Configuration\n");

        foreach ($infos->getMigrationsInfos() as $name => $value) {
            $string = (string) $value;

            if ($name === 'New Migrations') {
                $string = $value > 0 ? '<question>' . $value . '</question>' : '0';
            }

            if ($name === 'Executed Unavailable Migrations') {
                $string = $value > 0 ? '<error>' . $value . '</error>' : '0';
            }

            $this->writeStatusInfosLineAligned($output, $name, $string);
        }

        if (! $input->getOption('show-versions')) {
            return;
        }

        $migrations = $configuration->getMigrations();

        if ($migrations) {
            $output->writeln("\n <info>==</info> Available Migration Versions\n");

            $this->showVersions($migrations, $configuration, $output);
        }

        if (! count($infos->getExecutedUnavailableMigrations())) {
            return;
        }

        $output->writeln(
            "\n <info>==</info> Previously Executed Unavailable Migration Versions\n"
        );

        foreach ($infos->getExecutedUnavailableMigrations() as $executedUnavailableMigration) {
            $output->writeln(
                sprintf(
                    '    <comment>>></comment> %s (<comment>%s</comment>)',
                    $configuration->getDateTime($executedUnavailableMigration),
                    $executedUnavailableMigration
                )
            );
        }
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
     * @param Version[] $migrations
     */
    private function showVersions(array $migrations, Configuration $configuration, OutputInterface $output) : void
    {
        $migratedVersions = $configuration->getMigratedVersions();

        foreach ($migrations as $version) {
            $isMigrated = in_array($version->getVersion(), $migratedVersions, true);
            $status     = $isMigrated ? '<info>migrated</info>' : '<error>not migrated</error>';

            $migrationDescription = $version->getMigration()->getDescription()
                ? str_repeat(' ', 5) . $version->getMigration()->getDescription()
                : '';

            $formattedVersion = $configuration->getDateTime($version->getVersion());

            $output->writeln(sprintf(
                '    <comment>>></comment> %s (<comment>%s</comment>)%s%s%s',
                $formattedVersion,
                $version->getVersion(),
                str_repeat(' ', max(1, 49 - strlen($formattedVersion) - strlen($version->getVersion()))),
                $status,
                $migrationDescription
            ));
        }
    }
}
