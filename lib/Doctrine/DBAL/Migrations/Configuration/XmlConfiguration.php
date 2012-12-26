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
        $xml = simplexml_load_file($file);
        if (isset($xml->name)) {
            $this->setName((string) $xml->name);
        }
        if (isset($xml->table['name'])) {
            $this->setMigrationsTableName((string) $xml->table['name']);
        }
        if (isset($xml->{'migrations-namespace'})) {
            $this->setMigrationsNamespace((string) $xml->{'migrations-namespace'});
        }
        if (isset($xml->{'migrations-directory'})) {
            $migrationsDirectory = $this->getDirectoryRelativeToFile($file, (string) $xml->{'migrations-directory'});
            $this->setMigrationsDirectory($migrationsDirectory);
            $this->registerMigrationsFromDirectory($migrationsDirectory);
        }
        if (isset($xml->migrations->migration)) {
            foreach ($xml->migrations->migration as $migration) {
                $this->registerMigration((string) $migration['version'], (string) $migration['class']);
            }
        }
    }
}
