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

use Symfony\Component\Yaml\Yaml;

/**
 * Load migration configuration information from a YAML configuration file.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class YamlConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad($file)
    {
        $array = Yaml::parse($file);

        if (isset($array['name'])) {
            $this->setName($array['name']);
        }
        if (isset($array['table_name'])) {
            $this->setMigrationsTableName($array['table_name']);
        }
        if (isset($array['migrations_namespace'])) {
            $this->setMigrationsNamespace($array['migrations_namespace']);
        }
        if (isset($array['migrations_directory'])) {
            $migrationsDirectory = $this->getDirectoryRelativeToFile($file, $array['migrations_directory']);
            $this->setMigrationsDirectory($migrationsDirectory);
            $this->registerMigrationsFromDirectory($migrationsDirectory);
        }
        if (isset($array['migrations']) && is_array($array['migrations'])) {
            foreach ($array['migrations'] as $migration) {
                $this->registerMigration($migration['version'], $migration['class']);
            }
        }
    }
}
