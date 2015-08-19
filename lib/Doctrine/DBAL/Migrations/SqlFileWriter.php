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

namespace Doctrine\DBAL\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;

class SqlFileWriter
{
    private $migrationsTableName;

    private $destPath;

    /** @var null|OutputWriter */
    private $outputWriter;

    /**
     * @param string $migrationsTableName
     * @param string $destPath
     * @param \Doctrine\DBAL\Migrations\OutputWriter $outputWriter
     */
    public function __construct($migrationsTableName, $destPath, OutputWriter $outputWriter = null)
    {
        if (empty($migrationsTableName)) {
            $this->throwInvalidArgumentException('Migrations table name cannot be empty.');
        }
        $this->migrationsTableName = $migrationsTableName;

        if (empty($destPath)) {
            $this->throwInvalidArgumentException('Destination file must be specified.');
        }
        $this->destPath = $destPath;

        $this->outputWriter = $outputWriter;
    }

    /**
     * @param array $queriesByVersion array Keys are versions and values are arrays of SQL queries (they must be castable to string)
     * @param string $direction
     * @return int|bool
     */
    public function write(array $queriesByVersion, $direction)
    {
        $path   = $this->buildMigrationFilePath();
        $string = $this->buildMigrationFile($queriesByVersion, $direction);

        if ($this->outputWriter) {
            $this->outputWriter->write("\n" . sprintf('Writing migration file to "<info>%s</info>"', $path));
        }

        return file_put_contents($path, $string);
    }

    private function buildMigrationFile(array $queriesByVersion, $direction)
    {
        $string = sprintf("# Doctrine Migration File Generated on %s\n", date('Y-m-d H:i:s'));

        foreach ($queriesByVersion as $version => $queries) {
            $string .= "\n# Version " . $version . "\n";
            foreach ($queries as $query) {
                $string .= $query . ";\n";
            }


            $string .= $this->getVersionUpdateQuery($version, $direction);
        }

        return $string;
    }

    private function getVersionUpdateQuery($version, $direction)
    {
        if ($direction == Version::DIRECTION_DOWN) {
            $query = "DELETE FROM %s WHERE version = '%s';\n";
        } else {
            $query = "INSERT INTO %s (version) VALUES ('%s');\n";
        }

        return sprintf($query, $this->migrationsTableName, $version);
    }

    private function buildMigrationFilePath()
    {
        $path = $this->destPath;
        if (is_dir($path)) {
            $path = realpath($path);
            $path = $path . '/doctrine_migration_' . date('YmdHis') . '.sql';
        }

        return $path;
    }

    /**
     * This only exists for backwards-compatibiliy with DBAL 2.4
     */
    protected function throwInvalidArgumentException($message)
    {
        if (class_exists('Doctrine\DBAL\Exception\InvalidArgumentException')) {
            throw new InvalidArgumentException($message);
        } else {
            throw new DBALException($message);
        }
    }
}
