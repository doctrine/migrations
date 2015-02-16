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

use Doctrine\DBAL\Migrations\MigrationException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command for manually adding and deleting migration versions from the version table.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class VersionCommand extends AbstractCommand
{
    /**
     * The Migrations Configuration instance
     *
     * @var \Doctrine\DBAL\Migrations\Configuration\Configuration
     */
    private $configuration;

    /**
     * Whether or not the versions have to be marked as migrated or not
     *
     * @var boolean
     */
    private $markMigrated;

    protected function configure()
    {
        $this
            ->setName('migrations:version')
            ->setDescription('Manually add and delete migration versions from the version table.')
            ->addArgument('version', InputArgument::OPTIONAL, 'The version to add or delete.', null)
            ->addOption('add', null, InputOption::VALUE_NONE, 'Add the specified version.')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete the specified version.')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Apply to all the versions.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command allows you to manually add, delete or synchronize migration versions from the version table:

    <info>%command.full_name% YYYYMMDDHHMMSS --add</info>

If you want to delete a version you can use the <comment>--delete</comment> option:

    <info>%command.full_name% YYYYMMDDHHMMSS --delete</info>

If you want to synchronize by adding or deleting all migration versions available in the version table you can use the <comment>--all</comment> option:

    <info>%command.full_name% --add --all</info>
    <info>%command.full_name% --delete --all</info>

You can also execute this command without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>
EOT
            );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configuration = $this->getMigrationConfiguration($input, $output);

        if ($input->getOption('add') === false && $input->getOption('delete') === false) {
            throw new \InvalidArgumentException('You must specify whether you want to --add or --delete the specified version.');
        }

        $this->markMigrated = $input->getOption('add') ? true : false;

        $noInteraction = $input->getOption('no-interaction') ? true : false;
        if ($noInteraction === true) {
            $this->markAllAvailableVersions($input);
        } else {
            $confirmation = $this->getHelper('dialog')->askConfirmation($output, '<question>WARNING! You are about to add, delete or synchronize migration versions from the version table that could result in data lost. Are you sure you wish to continue? (y/n)</question>', false);
            if ($confirmation === true) {
                $this->markAllAvailableVersions($input);
            } else {
                $output->writeln('<error>Migration cancelled!</error>');
            }
        }

    }

    private function markAllAvailableVersions(InputInterface $input)
    {
        $version = $input->getArgument('version');

        if ($input->getOption('all') === true) {
            $availableVersions = $this->configuration->getAvailableVersions();
            foreach ($availableVersions as $version) {
                $this->mark($version, true);
            }
        } else {
            $this->mark($version);
        }
    }

    private function mark($version, $all = false)
    {
        if ( ! $this->configuration->hasVersion($version)) {
            throw MigrationException::unknownMigrationVersion($version);
        }

        $version = $this->configuration->getVersion($version);
        if ($this->markMigrated && $this->configuration->hasVersionMigrated($version)) {
            $marked = true;
            if (! $all) {
                throw new \InvalidArgumentException(sprintf('The version "%s" already exists in the version table.', $version));
            }
        }

        if ( ! $this->markMigrated && ! $this->configuration->hasVersionMigrated($version)) {
            $marked = false;
            if (! $all) {
                throw new \InvalidArgumentException(sprintf('The version "%s" does not exists in the version table.', $version));
            }
        }

        if ( ! isset($marked)) {
            if ($this->markMigrated) {
                $version->markMigrated();
            } else {
                $version->markNotMigrated();
            }
        }
    }
}
