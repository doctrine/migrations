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
 * CLI Task for adding and deleting migration versions from the version table.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class VersionTask extends AbstractTask
{
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $options = new OptionGroup(OptionGroup::CARDINALITY_N_N, array(
            new Option('configuration', '<PATH>', 'The migrations configuration file to use.'),
            new Option('add', '<YYYYMMDDHHMMSS>', 'The version to add.'),
            new Option('delete', '<YYYYMMDDHHMMSS>', 'The version to delete.'),
        ));

        $doc = $this->getDocumentation();
        $doc->setName('version')
            ->setDescription('Manually add and delete migration versions from the version table.')
            ->getOptionGroup()
                ->addOption($options);
    }

    /**
     * @inheritdoc
     */
    public function validate()
    {
        $arguments = $this->getArguments();
        
        if ( ! (isset($arguments['add']) || isset($arguments['delete']))) {
            throw new CliException('You must specify a version for either --add or --delete');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $arguments = $this->getArguments();
        $configuration = $this->_getMigrationConfiguration();
        $migration = new Migration($configuration);

        $version = isset($arguments['add']) ? $arguments['add'] : $arguments['delete'];
        $migrated = isset($arguments['add']) ? true : false;

        if ( ! $configuration->hasVersion($version)) {
            throw MigrationException::unknownMigrationVersion($version);
        }

        $version = $configuration->getVersion($version);
        $version->isMigrated($migrated);
    }
}