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
 * Command for manually adding and deleting migration versions from the version table.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class VersionCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('migrations:version')
            ->setDescription('Manually add and delete migration versions from the version table.')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to add or delete.', null)
            ->addOption('add', null, InputOption::VALUE_NONE, 'Add the specified version.')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete the specified version.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command allows you to manually add and delete migration versions from the version table:

    <info>%command.full_name% YYYYMMDDHHMMSS --add</info>

If you want to delete a version you can use the <comment>--delete</comment> option:

    <info>%command.full_name% YYYYMMDDHHMMSS --delete</info>
EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getMigrationConfiguration($input, $output);
        $migration = new Migration($configuration);

        if ($input->getOption('add') === false && $input->getOption('delete') === false) {
            throw new \InvalidArgumentException('You must specify whether you want to --add or --delete the specified version.');
        }

        $version = $input->getArgument('version');
        $markMigrated = $input->getOption('add') ? true : false;

        if ( ! $configuration->hasVersion($version)) {
            throw MigrationException::unknownMigrationVersion($version);
        }

        $version = $configuration->getVersion($version);
        if ($markMigrated && $configuration->hasVersionMigrated($version)) {
            throw new \InvalidArgumentException(sprintf('The version "%s" already exists in the version table.', $version));
        }

        if ( ! $markMigrated && ! $configuration->hasVersionMigrated($version)) {
            throw new \InvalidArgumentException(sprintf('The version "%s" does not exists in the version table.', $version));
        }

        if ($markMigrated) {
            $version->markMigrated();
        } else {
            $version->markNotMigrated();
        }
    }
}
