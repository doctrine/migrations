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

use Doctrine\Common\Cli\Tasks\AbstractTask as DoctrineAbstractTask,
    Doctrine\Common\Cli\CliException,
    Doctrine\Common\Cli\Option,
    Doctrine\Common\Cli\OptionGroup,
    DoctrineExtensions\Migrations\Migration,
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
abstract class AbstractTask extends DoctrineAbstractTask
{
    protected function _getMigrationConfiguration()
    {
        $arguments = $this->getArguments();
        $em = $this->getConfiguration()->getAttribute('em');

        if (isset($arguments['configuration'])) {
            $info = pathinfo($arguments['configuration']);
            $class = $info['extension'] === 'xml' ? 'DoctrineExtensions\Migrations\Configuration\XmlConfiguration' : 'DoctrineExtensions\Migrations\Configuration\YamlConfiguration';
            $configuration = new $class($em->getConnection());
            $configuration->load($arguments['configuration']);
        } else if (file_exists('configuration.xml')) {
            $configuration = new XmlConfiguration($em->getConnection());
            $configuration->load('configuration.xml');
        } else if (file_exists('configuration.yml')) {
            $configuration = new YamlConfiguration($em->getConnection());
            $configuration->load('configuration.yml');
        } else {
            $configuration = new Configuration($em->getConnection());
            if (isset($arguments['migrations-dir'])) {
                $configuration->registerMigrationsFromDirectory($arguments['migrations-dir']);
            }

            if (isset($arguments['version-table'])) {
                $configuration->setMigrationTableName($arguments['version-table']);
            }
        }

        $configuration->setPrinter($this->getPrinter());

        return $configuration;
    }
}