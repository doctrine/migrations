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

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputOption,
    Doctrine\DBAL\Migrations\Migration,
    Doctrine\DBAL\Migrations\MigrationException,
    Doctrine\DBAL\Migrations\OutputWriter,
    Doctrine\DBAL\Migrations\Configuration\Configuration,
    Doctrine\DBAL\Migrations\Configuration\YamlConfiguration,
    Doctrine\DBAL\Migrations\Configuration\XmlConfiguration;

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
     * @var Configuration
     */
    private $configuration;

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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return Configuration
     */
    protected function getMigrationConfiguration(InputInterface $input, OutputInterface $output)
    {
        if ( ! $this->configuration) {
            $outputWriter = new OutputWriter(function($message) use ($output) {
                return $output->writeln($message);
            });

            if ($this->getApplication()->getHelperSet()->has('db')) {
                $conn = $this->getHelper('db')->getConnection();
            } else if($input->getOption('db-configuration')) {
                if (!file_exists($input->getOption('db-configuration'))) {
                    throw new \InvalidArgumentException("The specified connection file is not a valid file.");
                }

                $params = include($input->getOption('db-configuration'));
                if (!is_array($params)) {
                    throw new \InvalidArgumentException('The connection file has to return an array with database configuration parameters.');
                }
                $conn = \Doctrine\DBAL\DriverManager::getConnection($params);
            } else if (file_exists('migrations-db.php')) {
                $params = include("migrations-db.php");
                if (!is_array($params)) {
                    throw new \InvalidArgumentException('The connection file has to return an array with database configuration parameters.');
                }
                $conn = \Doctrine\DBAL\DriverManager::getConnection($params);
            } else {
                throw new \InvalidArgumentException('You have to specify a --db-configuration file or pass a Database Connection as a dependency to the Migrations.');
            }

            if ($input->getOption('configuration')) {
                $info = pathinfo($input->getOption('configuration'));
                $class = $info['extension'] === 'xml' ? 'Doctrine\DBAL\Migrations\Configuration\XmlConfiguration' : 'Doctrine\DBAL\Migrations\Configuration\YamlConfiguration';
                $configuration = new $class($conn, $outputWriter);
                $configuration->load($input->getOption('configuration'));
            } else if (file_exists('migrations.xml')) {
                $configuration = new XmlConfiguration($conn, $outputWriter);
                $configuration->load('migrations.xml');
            } else if (file_exists('migrations.yml')) {
                $configuration = new YamlConfiguration($conn, $outputWriter);
                $configuration->load('migrations.yml');
            } else {
                $configuration = new Configuration($conn, $outputWriter);
            }
            $this->configuration = $configuration;
        }
        return $this->configuration;
    }
}
