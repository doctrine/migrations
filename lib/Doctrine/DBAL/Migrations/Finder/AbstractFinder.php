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

namespace Doctrine\DBAL\Migrations\Finder;

/**
 * Abstract base class for MigrationFinders
 *
 * @since   1.0.0-alpha3
 */
abstract class AbstractFinder implements MigrationFinderInterface
{
    protected static function requireOnce($path)
    {
        require_once $path;
    }

    protected function getRealPath($directory)
    {
        $dir = realpath($directory);
        if (false === $dir || !is_dir($dir)) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot load migrations from "%s" because it is not a valid directory',
                $directory
            ));
        }

        return $dir;
    }

    /**
     * Load the migrations and return an array of thoses loaded migrations
     * @param $files array of migration filename found
     * @param $namespace namespace of thoses migrations
     * @return array constructed with the migration name as key and the value is the fully qualified name of the migration
     */
    protected function loadMigrations($files, $namespace)
    {
        $migrations = [];
        foreach ($files as $file) {
            static::requireOnce($file);
            $className = basename($file, '.php');
            $version = substr($className, 7);
            $migrations[$version] = sprintf('%s\\%s', $namespace, $className);
        }

        return $migrations;
    }
}
