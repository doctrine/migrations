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

class ConfigurationHelper {

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Connection $connection=null, Configuration $configuration=null)
    {
        $this->connection = $connection;
        $this->configuration = $configuration;
    }

    public function getMigrationConfig(InputInterface $input, OutputWriter $outputWriter)
    {
        if ($input->getOption('configuration')) {
            $configuration = $this->loadConfig($input->getOption('configuration'), $outputWriter);
            $outputWriter->write("Loading configuration from command option: " . $input->getOption('configuration'));
        } elseif ($this->configuration) {
            $configuration = $this->configuration;
        } elseif ($this->configExists('migrations.xml')) {
            $configuration = $this->loadConfig('migrations.xml', $outputWriter);
            $outputWriter->write("Loading configuration from file: migrations.xml");
        } elseif ($this->configExists('migrations.yml')) {
            $configuration = $this->loadConfig('migrations.yml', $outputWriter);
            $outputWriter->write("Loading configuration from file: migrations.yml");
        } elseif ($this->configExists('migrations.yaml')) {
            $configuration = $this->loadConfig('migrations.yaml', $outputWriter);
            $outputWriter->write("Loading configuration from file: migrations.yaml");
        } else {
            $configuration = new Configuration($this->connection, $outputWriter);
        }
        return $configuration;
    }


    private function configExists($config)
    {
        return file_exists($config);
    }

    private function loadConfig($config, OutputWriter $outputWriter)
    {
        $info = pathinfo($config);
        $class = $info['extension'] === 'xml' ? 'Doctrine\DBAL\Migrations\Configuration\XmlConfiguration' : 'Doctrine\DBAL\Migrations\Configuration\YamlConfiguration';
        $configuration = new $class($this->connection, $outputWriter);
        $configuration->load($config);
        return $configuration;
    }

}