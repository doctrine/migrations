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
    Doctrine\ORM\Tools\ClassMetadataReader,
    Doctrine\ORM\Tools\SchemaTool;

/**
 * CLI Task for generate migration classes by comparing your current database
 * to your ORM mapping information.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class DiffTask extends GenerateTask
{
    private $_configuration;

    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $options = new OptionGroup(OptionGroup::CARDINALITY_N_N, array(
            new Option('from', '<FROM>', 'The path to mapping information.'),
            new Option('configuration', '<PATH>', 'The migrations configuration file to use.'),
            new Option('migrations-dir', '<PATH>', 'The path to a directory containing migration classes.'),
            new Option('version-table', '<PATH>', 'The name of the version table for these migrations.'),
        ));

        $doc = $this->getDocumentation();
        $doc->setName('diff')
            ->setDescription('Generate migration classes by comparing your current database to your ORM mapping information.')
            ->getOptionGroup()
                ->addOption($options);
    }

    /**
     * @inheritdoc
     */
    public function validate()
    {
        $arguments = $this->getArguments();
        if ( ! isset($arguments['from'])) {
            throw new CliException('You must specify the --from option with a value to some entity mapping information.');
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $arguments = $this->getArguments();
        $printer = $this->getPrinter();
        $em = $this->getConfiguration()->getAttribute('em');
        $platform = $em->getConnection()->getDatabasePlatform();

        $this->_configuration = $this->_getMigrationConfiguration();

        $reader = new ClassMetadataReader();
        $reader->setEntityManager($em);
        $reader->addMappingSource($arguments['from']);
        $classes = $reader->getMetadatas(true);

        if (empty($classes)) {
            $printer->writeln('No mapping information to process.', 'ERROR');
            return;
        }

        $tool = new SchemaTool($em);

        $fromSchema = $em->getConnection()->getSchemaManager()->createSchema();
        $toSchema = $tool->getSchemaFromMetadata($classes);
        $up = $this->_buildCodeFromSql($fromSchema->getMigrateToSql($toSchema, $platform));
        $down = $this->_buildCodeFromSql($fromSchema->getMigrateFromSql($toSchema, $platform));

        if ( ! $up && ! $down) {
            $printer->writeln('No changes detected in your mapping information.', 'ERROR');
            return;
        }

        $version = date('YmdHms');
        $this->_generateMigration($version, $up, $down);
    }

    private function _buildCodeFromSql(array $sql)
    {
        $code = array();
        foreach ($sql as $query) {
            if (strpos($query, $this->_configuration->getMigrationTableName()) !== false) {
                continue;
            }
            $code[] = "\$this->_addSql('" . $query . "');";
        }
        return implode("\n", $code);
    }
}