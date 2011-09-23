<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\DBAL\Migrations\Tools\Console\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputOption,
    Doctrine\DBAL\Migrations\Migration,
    Doctrine\DBAL\Migrations\MigrationException,
    Doctrine\DBAL\Migrations\Configuration\Configuration,
    Doctrine\DBAL\Migrations\Configuration\YamlConfiguration,
    Doctrine\DBAL\Migrations\Configuration\XmlConfiguration;

/**
 * Command for manually adding all versions up to an arbitrary version, (defaulting to lastest)
 * including any in-between from current to the version table.  This is useful if you're starting
 * with a freshly created database based off the lastest version of the schema from the schema
 * metadata.  Presumably, you'd have arrived at the latest schema by running through the
 * migrations in the first place.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Nathan Nobbe <nathan@moxune.com>
 */
class VersionToCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('migrations:versionto')
            ->setDescription('Manually add all versions through latest (imagine starting from scratch w/ the latest schema)')
            ->addArgument('versionto', InputArgument::OPTIONAL, 'The version to manually jump to (default: latest).', null)
            ->addOption('show-versions', null, InputOption::VALUE_NONE, 'This will display a list of all available migrations and their status')
            ->addOption('down', null, InputOption::VALUE_NONE, 'Execute the migration down.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command  manually adds all migrations through latest:

EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // load configuration
        $configuration = $this->getMigrationConfiguration($input, $output);

        // extract direction from arguments
        $bUp = $input->getOption('down') ? false : true;

        // extract version from arguments
        $version = $input->getOption('version');

        $bLatest = false;
        if(empty($version))
            if($bUp) {
                $version = $configuration->getLatestVersion();
                $bLatest = true;
            } else
                $version = 0;

        // write a header message indicating a manual migration (not actually running up or down on a Migration)
        if($bUp) {
            $sMsg = PHP_EOL . " Manually migrating up to version: <comment>$version</comment>";

            // highlight migration to lastest version
            if($bLatest)
                $sMsg .= ' (<info>latest</info>)';

            $output->writeln($sMsg . PHP_EOL);

            // fetch the migrations to mark migrated
            $migrations = array_keys($configuration->getMigrationsToExecute('up', $version));
        } else {
            $sMsg = PHP_EOL . " Manually migrating down to version: <info>$version</info>";

            // highlght migration to earliest version
            if($version == 0)
                $sMsg .= ' (<info>earliest</info>)';

            $output->writeln($sMsg . PHP_EOL);

            // fetch the migrations to mark not migrated
            $migrations = array_keys($configuration->getMigrationsToExecute('down', $version));
        }

        // loop over the migrations marking them migrated or not migrated as appropriate
        foreach($migrations as $sMigration) {
            if($bUp) {
                $configuration->getVersion($sMigration)->markMigrated();
                $output->writeln(" Marked version[<comment>$sMigration</comment>] as <info>migrated</info>");
            } else {
                $configuration->getVersion($sMigration)->markNotMigrated();
                $output->writeln(" Marked version[<comment>$sMigration</comment>] as <info>not migrated</info>");
            }
        }

        // code borrowed from bottom of StatusCommand
        // dump a robust listing of migrations
        $showVersions = $input->getOption('show-versions') ? true : false;
        if ($showVersions === true) {
            if ($migrations = $configuration->getMigrations()) {
                $output->writeln("\n <info>==</info> Migration Versions\n");
                $migratedVersions = $configuration->getMigratedVersions();
                foreach ($migrations as $version) {
                    $isMigrated = in_array($version->getVersion(), $migratedVersions);
                    $status = $isMigrated ? '<info>migrated</info>' : '<error>not migrated</error>';
                    $output->writeln('    <comment>>></comment> ' . $configuration->formatVersion($version->getVersion()) . ' (<comment>' . $version->getVersion() . '</comment>)  ' . $status);
                }
            }
        }
    }
}
