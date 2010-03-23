<?php
/*
 *  $Id$
 *
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
 
namespace DoctrineExtensions\Migrations\Tools\Cli\Tasks;

use Doctrine\Common\Cli\CliException,
    Doctrine\Common\Cli\Option,
    Doctrine\Common\Cli\OptionGroup,
    DoctrineExtensions\Migrations\Migration,
    DoctrineExtensions\Migrations\MigrationException,
    DoctrineExtensions\Migrations\Configuration\Configuration,
    DoctrineExtensions\Migrations\Configuration\YamlConfiguration,
    DoctrineExtensions\Migrations\Configuration\XmlConfiguration;

/**
 * CLI Task to see the status of some Doctrine migrations
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class StatusTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $options = new OptionGroup(OptionGroup::CARDINALITY_N_N, array(
            new Option('configuration', '<PATH>', 'The migrations configuration file to use.'),
            new Option('migrations-dir', '<PATH>', 'The path to a directory containing migration classes.'),
            new Option('version-table', '<PATH>', 'The name of the version table for these migrations.'),
        ));

        $doc = $this->getDocumentation();
        $doc->setName('status')
            ->setDescription('View the status of some migrations.')
            ->getOptionGroup()
                ->addOption($options);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $printer = $this->getPrinter();
        $arguments = $this->getArguments();
        $configuration = $this->_getMigrationConfiguration();

        $currentVersion = $configuration->getCurrentVersion();
        if ($currentVersion) {
            $currentVersionFormatted = $configuration->formatVersion($currentVersion) . ' ('.$currentVersion.')';
        } else {
            $currentVersionFormatted = 0;
        }
        $latestVersion = $configuration->getLatestVersion();
        if ($latestVersion) {
            $latestVersionFormatted = $configuration->formatVersion($latestVersion) . ' ('.$latestVersion.')';
        } else {
            $latestVersionFormatted = 0;
        }
        $executedMigrations = $configuration->getNumberOfExecutedMigrations();
        $availableMigrations = $configuration->getNumberOfAvailableMigrations();
        $newMigrations = $availableMigrations - $executedMigrations;

        $printer->writeln(' == Overview', 'INFO');
        $printer->writeln('');

        $info = array(
            'Table Name'            => $configuration->getMigrationTableName(),
            'Current Version'       => $currentVersionFormatted,
            'Latest Version'        => $latestVersionFormatted,
            'Executed Migrations'   => $executedMigrations,
            'Available Migrations'  => $availableMigrations,
            'New Migrations'        => $printer->format($newMigrations, $newMigrations > 0 ? 'ERROR' : 'INFO')
        );
        foreach ($info as $name => $value) {
            $printer->writeln('    >> ' . $printer->format($name, 'HEADER') . ': ' . str_repeat(' ', 50 - strlen($name)) . $printer->format($value, 'INFO'));
        }

        $printer->writeln('');
        $printer->writeln(' == Status', 'INFO');
        $printer->writeln('');

        foreach ($configuration->getMigrations() as $version) {
            $isMigrated = $version->isMigrated();
            $status = $printer->format(
                $isMigrated ? 'migrated' : 'not migrated',
                $isMigrated ? 'INFO' : 'ERROR'
            );
            $printer->writeln('    >> ' . $printer->format($configuration->formatVersion($version->getVersion()) . ' (' . $version->getVersion() . ')', 'HEADER') . str_repeat(' ', 30 - strlen($name)) . $status);
        }
        $printer->writeln('');
    }
}