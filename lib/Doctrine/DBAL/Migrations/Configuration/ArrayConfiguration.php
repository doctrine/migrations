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
class ArrayConfiguration extends Configuration
{
    public function setOptions(array $options) {
        
        if (isset($options['name'])) {
            
            $this->setName($options['name']);
        }
        
        if (isset($options['namespace'])) {
            
            $this->setMigrationsNamespace($options['namespace']);
        }
        
        if (isset($options['table_name'])) {
            
            $this->setMigrationsTableName($options['table_name']);
        }
        
        if (isset($options['directories'])) {
            
            if (!is_array($options['directories'])) {
                
                throw new \Exception('directories config must be an array');
            }
            
            foreach ($options['directories'] as $path) {
                
                $this->registerMigrationsFromDirectory($path);
            }
        }
        
        if (isset($options['migrations'])) {
            
            if (!is_array($options['migrations'])) {
                
                throw new \Exception('migrations config must be an array');
            }
            
            foreach ($options['migrations'] as $migration) {
                
                $this->registerMigration($migration['version'], $migration['class']);
            }
        }
    }
}