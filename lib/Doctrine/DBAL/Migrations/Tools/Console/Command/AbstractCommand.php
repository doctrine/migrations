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

use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Configuration\YamlConfiguration;
use Doctrine\DBAL\Migrations\Configuration\XmlConfiguration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * CLI Command for adding and deleting migration versions from the version table.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    /**
     * The configuration property only contains the configuration injected by the setter.
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * The migrationConfiguration property contains the configuration
     * created taking into account the command line options.
     *
     * @var Configuration
     */
    private $migrationConfiguration;

    /**
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    protected function configure()
    {
        $this->addOption('configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a migrations configuration file.');
        $this->addOption('db-configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a database connection configuration file.');
    }

    protected function outputHeader(Configuration $configuration, OutputInterface $output)
    {
        $name = $configuration->getName();
        $name = $name ? $name : 'Doctrine Database Migrations';
        $name = str_repeat(' ', 20) . $name . str_repeat(' ', 20);
        $output->writeln('<question>' . str_repeat(' ', strlen($name)) . '</question>');
        $output->writeln('<question>' . $name . '</question>');
        $output->writeln('<question>' . str_repeat(' ', strlen($name)) . '</question>');
        $output->writeln('');
    }

    public function setMigrationConfiguration(Configuration $config)
    {
        $this->configuration = $config;
    }

    /**
     * When any (config) command line option is passed to the migration the migrationConfiguration
     * property is set with the new generated configuration.
     * If no (config) option is passed the migrationConfiguration property is set to the value
     * of the configuration one (if any).
     * Else a new configuration is created and assigned to the migrationConfiguration property.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return Configuration
     */
    protected function getMigrationConfiguration(InputInterface $input, OutputInterface $output)
    {
        if (!$this->migrationConfiguration) {
            if ($input->getOption('configuration')) {
                $info = pathinfo($input->getOption('configuration'));
                $class = $info['extension'] === 'xml' ? 'Doctrine\DBAL\Migrations\Configuration\XmlConfiguration' : 'Doctrine\DBAL\Migrations\Configuration\YamlConfiguration';
                $configuration = new $class($this->getConnection($input), $this->getOutputWriter($output));
                $configuration->load($input->getOption('configuration'));
            } elseif ($this->configuration) {
                $configuration = $this->configuration;
            } elseif (file_exists('migrations.xml')) {
                $configuration = new XmlConfiguration($this->getConnection($input), $this->getOutputWriter($output));
                $configuration->load('migrations.xml');
            } elseif (file_exists('migrations.yml')) {
                $configuration = new YamlConfiguration($this->getConnection($input), $this->getOutputWriter($output));
                $configuration->load('migrations.yml');
            } elseif (file_exists('migrations.yaml')) {
                $configuration = new YamlConfiguration($this->getConnection($input), $this->getOutputWriter($output));
                $configuration->load('migrations.yaml');
            } else {
                $configuration = new Configuration($this->getConnection($input), $this->getOutputWriter($output));
            }
            $this->migrationConfiguration = $configuration;
        }

        return $this->migrationConfiguration;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Doctrine\DBAL\Migrations\OutputWriter
     */
    private function getOutputWriter(OutputInterface $output)
    {
        if (!$this->outputWriter) {
            $this->outputWriter = new OutputWriter(function ($message) use ($output) {
                return $output->writeln($message);
            });
        }

        return $this->outputWriter;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     *
     * @return \Doctrine\DBAL\Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getConnection(InputInterface $input)
    {
        if (!$this->connection) {
            if ($input->getOption('db-configuration')) {
                if (!file_exists($input->getOption('db-configuration'))) {
                    throw new \InvalidArgumentException("The specified connection file is not a valid file.");
                }

                $params = include $input->getOption('db-configuration');
                if (!is_array($params)) {
                    throw new \InvalidArgumentException('The connection file has to return an array with database configuration parameters.');
                }
                $this->connection = \Doctrine\DBAL\DriverManager::getConnection($params);
            } elseif (file_exists('migrations-db.php')) {
                $params = include 'migrations-db.php';
                if (!is_array($params)) {
                    throw new \InvalidArgumentException('The connection file has to return an array with database configuration parameters.');
                }
                $this->connection = \Doctrine\DBAL\DriverManager::getConnection($params);
            } elseif ($this->getHelperSet()->has('connection')) {
                $this->connection = $this->getHelper('connection')->getConnection();
            } else {
                throw new \InvalidArgumentException('You have to specify a --db-configuration file or pass a Database Connection as a dependency to the Migrations.');
            }
        }

        return $this->connection;
    }
}
