<?php

namespace Doctrine\DBAL\Migrations\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command to view the status of a set of migrations.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class StatusCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('migrations:status')
            ->setDescription('View the status of a set of migrations.')
            ->addOption('show-versions', null, InputOption::VALUE_NONE, 'This will display a list of all available migrations and their status')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command outputs the status of a set of migrations:

    <info>%command.full_name%</info>

You can output a list of all available migrations and their status with <comment>--show-versions</comment>:

    <info>%command.full_name% --show-versions</info>
EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getMigrationConfiguration($input, $output);

        $infos = new MigrationStatusInfosHelper($configuration);

        $output->writeln("\n <info>==</info> Configuration\n");
        foreach ($infos->getMigrationsInfos() as $name => $value) {
            if ($name == 'New Migrations') {
                $value = $value > 0 ? '<question>' . $value . '</question>' : 0;
            }
            if ($name == 'Executed Unavailable Migrations') {
                $value = $value > 0 ? '<error>' . $value . '</error>' : 0;
            }
            $this->writeStatusInfosLineAligned($output, $name, $value);
        }

        if ($input->getOption('show-versions')) {
            if ($migrations = $configuration->getMigrations()) {
                $output->writeln("\n <info>==</info> Available Migration Versions\n");

                $this->showVersions($migrations, $configuration, $output);
            }

            if (count($infos->getExecutedUnavailableMigrations())) {
                $output->writeln("\n <info>==</info> Previously Executed Unavailable Migration Versions\n");
                foreach ($infos->getExecutedUnavailableMigrations() as $executedUnavailableMigration) {
                    $output->writeln('    <comment>>></comment> ' . $configuration->getDateTime($executedUnavailableMigration) .
                        ' (<comment>' . $executedUnavailableMigration . '</comment>)');
                }
            }
        }
    }

    private function writeStatusInfosLineAligned(OutputInterface $output, $title, $value)
    {
        $output->writeln('    <comment>>></comment> ' . $title . ': ' . str_repeat(' ', 50 - strlen($title)) . $value);
    }

    private function showVersions($migrations, Configuration $configuration, OutputInterface $output)
    {
        $migratedVersions = $configuration->getMigratedVersions();

        foreach ($migrations as $version) {
            $isMigrated = in_array($version->getVersion(), $migratedVersions, true);
            $status     = $isMigrated ? '<info>migrated</info>' : '<error>not migrated</error>';

            $migrationDescription = $version->getMigration()->getDescription()
                ? str_repeat(' ', 5) . $version->getMigration()->getDescription()
                : '';

            $formattedVersion = $configuration->getDateTime($version->getVersion());

            $output->writeln('    <comment>>></comment> ' . $formattedVersion .
                ' (<comment>' . $version->getVersion() . '</comment>)' .
                str_repeat(' ', max(1, 49 - strlen($formattedVersion) - strlen($version->getVersion()))) .
                $status . $migrationDescription);
        }
    }
}
