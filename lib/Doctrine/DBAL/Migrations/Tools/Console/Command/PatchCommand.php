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
    Doctrine\DBAL\Migrations\Patch,
    Doctrine\DBAL\Migrations\Configuration\Configuration,
    Doctrine\DBAL\Migrations\Configuration\YamlConfiguration,
    Doctrine\DBAL\Migrations\Configuration\XmlConfiguration;

class PatchCommand extends AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('migrations:patch')
            ->setDescription('Execute any missing migrations up to current version')
            ->addOption('write-sql', null, InputOption::VALUE_NONE, 'The path to output the migration SQL file instead of executing it.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Execute the migration as a dry run.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command executes any missing migrations up to current version:

    <info>%command.full_name%</info>

You can also execute the migration as a <comment>--dry-run</comment>:

    <info>%command.full_name% YYYYMMDDHHMMSS --dry-run</info>

You can output the would be executed SQL statements to a file with <comment>--write-sql</comment>:

    <info>%command.full_name% YYYYMMDDHHMMSS --write-sql</info>

Or you can also execute the migration without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>

EOT
        );

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getMigrationConfiguration($input, $output);
        $patch = new Patch($configuration);

        $this->outputHeader($configuration, $output);

        if ($path = $input->getOption('write-sql')) {
            $path = is_bool($path) ? getcwd() : $path;
            $patch->writeSqlFile($path);
        } elseif ($input->getOption('dry-run') || $input->getOption('no-interaction')) {
            $patch->patch(true);
        } else {
            $confirmation = $this->getHelper('dialog')->askConfirmation($output, '<question>WARNING! You are about to execute a database migration that could result in schema changes and data lost. Are you sure you wish to continue? (y/n)</question>', 'y');
            if ($confirmation) {
                $patch->patch(false);
            } else {
                $output->writeln('<error>Patch cancelled!</error>');
            }
        }
    }
}
