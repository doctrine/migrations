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

namespace Doctrine\DBAL\Migrations\Tools\Console\Helper;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class ConfigurationHelper
 * @package Doctrine\DBAL\Migrations\Tools\Console\Helper
 * @internal
 */
final class ConfigurationHelper
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Connection $connection = null, Configuration $configuration = null)
    {
        $this->connection    = $connection;
        $this->configuration = $configuration;
    }

    public function getMigrationConfig(InputInterface $input, OutputWriter $outputWriter)
    {
        /**
         * If a configuration option is passed to the command line, use that configuration
         * instead of any other one.
         */
        if ($input->getOption('configuration')) {
            $outputWriter->write("Loading configuration from command option: " . $input->getOption('configuration'));

            return $this->loadConfig($input->getOption('configuration'), $outputWriter);
        }

        /**
         * If a configuration has already been set using DI or a Setter use it.
         */
        if ($this->configuration) {
            $outputWriter->write("Loading configuration from the integration code of your framework (setter).");

            return $this->configuration;
        }

        /**
         * If no any other config has been found, look for default config file in the path.
         */
        $defaultConfig = array(
            'migrations.xml',
            'migrations.yml',
            'migrations.yaml',
        );
        foreach ($defaultConfig as $config) {
            if ($this->configExists($config)) {
                $outputWriter->write("Loading configuration from file: $config");

                return $this->loadConfig($config, $outputWriter);
            }
        }

        return new Configuration($this->connection, $outputWriter);
    }


    private function configExists($config)
    {
        return file_exists($config);
    }

    private function loadConfig($config, OutputWriter $outputWriter)
    {
        $info          = pathinfo($config);
        $class         = 'Doctrine\DBAL\Migrations\Configuration';
        $class        .= $info['extension'] === 'xml' ? '\XmlConfiguration' : '\YamlConfiguration';
        $configuration = new $class($this->connection, $outputWriter);
        $configuration->load($config);

        return $configuration;
    }
}
