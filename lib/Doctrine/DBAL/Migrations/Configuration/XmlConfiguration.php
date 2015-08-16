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

namespace Doctrine\DBAL\Migrations\Configuration;

use Doctrine\DBAL\Migrations\MigrationException;

/**
 * Load migration configuration information from a XML configuration file.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class XmlConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad($file)
    {
        libxml_use_internal_errors(true);
        $xml = new \DOMDocument();
        $xml->load($file);
        if (!$xml->schemaValidate(__DIR__ . DIRECTORY_SEPARATOR . "XML" . DIRECTORY_SEPARATOR . "configuration.xsd")) {
            libxml_clear_errors();
            throw MigrationException::configurationNotValid('XML configuration did not pass the validation test.');
        }

        $xml = simplexml_load_file($file, "SimpleXMLElement", LIBXML_NOCDATA);
        $config = [];

        if (isset($xml->name)) {
            $config['name'] = (string) $xml->name;
        }
        if (isset($xml->table['name'])) {
            $config['table_name'] = (string) $xml->table['name'];
        }
        if (isset($xml->{'migrations-namespace'})) {
            $config['migrations_namespace'] = (string) $xml->{'migrations-namespace'};
        }
        if (isset($xml->{'organize-migrations'})) {
            $config['organize_migrations'] = $xml->{'organize-migrations'};
        }
        if (isset($xml->{'migrations-directory'})) {
            $config['migrations_directory'] = $this->getDirectoryRelativeToFile($file, (string) $xml->{'migrations-directory'});
        }
        if (isset($xml->migrations->migration)) {
            $config['migrations'] = $xml->migrations->migration;
        }

        $this->setConfiguration($config);
    }
}
