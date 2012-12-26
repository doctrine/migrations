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
 * Abstract Migration Configuration class for loading configuration information
 * from a configuration file (xml or yml).
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class AbstractFileConfiguration extends Configuration
{
    /**
     * The configuration file used to load configuration information
     *
     * @var string
     */
    private $file;

    /**
     * Whether or not the configuration file has been loaded yet or not
     *
     * @var boolean
     */
    private $loaded = false;

    /**
     * Load the information from the passed configuration file
     *
     * @param string $file The path to the configuration file
     *
     * @return void
     *
     * @throws MigrationException Throws exception if configuration file was already loaded
     */
    public function load($file)
    {
        if ($this->loaded) {
            throw MigrationException::configurationFileAlreadyLoaded();
        }
        if (file_exists($path = getcwd() . '/' . $file)) {
            $file = $path;
        }
        $this->file = $file;
        $this->doLoad($file);
        $this->loaded = true;
    }

    protected function getDirectoryRelativeToFile($file, $input)
    {
        $path = realpath(dirname($file) . '/' . $input);
        if ($path !== false) {
            $directory = $path;
        } else {
            $directory = $input;
        }

        return $directory;
    }

    public function getFile()
    {
        return $this->file;
    }

    /**
     * Abstract method that each file configuration driver must implement to
     * load the given configuration file whether it be xml, yaml, etc. or something
     * else.
     *
     * @param string $file The path to a configuration file.
     */
    abstract protected function doLoad($file);
}
