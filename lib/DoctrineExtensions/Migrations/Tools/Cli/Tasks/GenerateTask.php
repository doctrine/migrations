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
 * CLI Task for generating new blank migration classes
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class GenerateTask extends AbstractTask
{
    private static $_template =
'<?php

namespace DoctrineMigrations;

use DoctrineExtensions\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

class Version<version> extends AbstractMigration
{
    public function up(Schema $schema)
    {
<up>
    }

    public function down(Schema $schema)
    {
<down>
    }
}';
    /**
     * @inheritdoc
     */
    public function buildDocumentation()
    {
        $options = new OptionGroup(OptionGroup::CARDINALITY_N_N, array(
            new Option('migrations-dir', '<PATH>', 'The path to a directory containing migration classes.'),
        ));

        $doc = $this->getDocumentation();
        $doc->setName('generate')
            ->setDescription('Manually add and delete migration versions from the version table.')
            ->getOptionGroup()
                ->addOption($options);
    }

    /**
     * @inheritdoc
     */
    public function validate()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $version = date('YmdHms');
        $this->_generateMigration($version);
    }

    protected function _generateMigration($version, $up = null, $down = null)
    {
        $printer = $this->getPrinter();
        $arguments = $this->getArguments();

        $placeHolders = array(
            '<version>',
            '<up>',
            '<down>'
        );
        $replacements = array(
            $version,
            $up ? "        " . implode("\n        ", explode("\n", $up)) : null,
            $down ? "        " . implode("\n        ", explode("\n", $down)) : null
        );
        $code = str_replace($placeHolders, $replacements, self::$_template);
        $dir = isset($arguments['migrations-dir']) ? $arguments['migrations-dir'] : getcwd();
        $dir = rtrim($dir, '/');
        $path = $dir . '/Version' . $version . '.php';

        $printer->writeln(sprintf('Writing new migration class to "' . $printer->format('%s', 'INFO') . '"', $path));

        $printer->writeln('');
        $printer->writeln("     ".implode("\n     ", explode("\n", $code)));

        file_put_contents($path, $code);
    }
}