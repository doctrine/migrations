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
    DoctrineExtensions\Migrations\Configuration\Configuration,
    DoctrineExtensions\Migrations\Configuration\YamlConfiguration,
    DoctrineExtensions\Migrations\Configuration\XmlConfiguration;

/**
 * CLI Task for executing Doctrine migrations
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class MigrateTask extends AbstractTask
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
            new Option('version', '<FROM>', 'The version to migrate to.'),
            new Option('write-sql', '<PATH>', 'The path to output the migration SQL file instead of executing it.'),
            new Option('dry-run', null, 'Whether to execute the migrtion as a dry run.')
        ));

        $doc = $this->getDocumentation();
        $doc->setName('migrate')
            ->setDescription('Execute a migration to a specified version or the current version.')
            ->getOptionGroup()
                ->addOption($options);
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $arguments = $this->getArguments();
        $version = isset($arguments['version']) ? $arguments['version'] : null;

        $configuration = $this->_getMigrationConfiguration();
        $migration = new Migration($configuration);

        if (isset($arguments['write-sql'])) {
            $migration->writeSqlFile($arguments['write-sql'], $version);
        } else {
            $migration->migrate($version, isset($arguments['dry-run']) ? true : false);
        }
    }
}