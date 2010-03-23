<?php
/*
 *  $Id$
 *
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

namespace DoctrineExtensions\Migrations;

use DoctrineExtensions\Migrations\Configuration\Configuration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Class for running migrations to the current version or a manually specified version.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Migration
{
    /** The Doctrine\DBAL\Connection instance we are migrating */
    private $_connection;

    /** CLI Printer instance used for useful output about your migration */
    private $_printer;

    public function __construct(Configuration $configuration)
    {
        $this->_configuration = $configuration;
        $this->_printer = $configuration->getPrinter();
    }

    /**
     * Get the array of versions and SQL queries that would be executed for 
     * each version but do not execute anything.
     *
     * @param string $to   The version to migrate to.
     * @return array $sql  The array of SQL queries.
     */
    public function getSql($to = null)
    {
        return $this->migrate($to, true);
    }

    /**
     * Write a migration SQL file to the given path
     *
     * @param string $path   The path to write the migration SQL file.
     * @param string $to     The version to migrate to.
     * @return bool $written
     */
    public function writeSqlFile($path, $to = null)
    {
        $sql = $this->getSql($to);

        $from = $this->_configuration->getCurrentVersion();
        if ($to === null) {
            $to = $this->_configuration->getLatestVersion();
        }

        $string  = sprintf("# Doctrine Migration File Generated on %s\n", date('Y-m-d H:m:s'));
        $string .= sprintf("# Migrating from %s to %s\n", $from, $to);

        foreach ($sql as $version => $queries) {
            $string .= "\n# Version " . $version . "\n";
            foreach ($queries as $query) {
                $string .= $query . ";\n";
            }
        }
        if (is_dir($path)) {
            $path = realpath($path);
            $path = $path . '/doctrine_migration_' . date('YmdHms') . '.sql';
        }

        $this->_printer->writeln('');
        $this->_announce(sprintf('Writing migration file to "'.$this->_printer->format('%s', 'KEYWORD').'"', $path));

        return file_put_contents($path, $string);
    }

    /**
     * Run a migration to the current version or the given target version.
     *
     * @param string $to      The version to migrate to.
     * @param string $dryRun  Whether or not to make this a dry run and not execute anything.
     * @return array $sql     The array of migration sql statements
     */
    public function migrate($to = null, $dryRun = false)
    {
        if ($to === null) {
            $to = $this->_configuration->getLatestVersion();
        }

        $from = $this->_configuration->getCurrentVersion();

        $this->_announce(sprintf('Current version is ' . $this->_printer->format('%s', 'KEYWORD'), $from));

        $migrations = $this->_configuration->getMigrations();
        if ( ! isset($migrations[$to]) && $to > 0) {
            $this->_printer->writeln(sprintf('Migration version %s does not exist', $to), 'ERROR');
            return false;
        }

        $direction = $from > $to ? 'down' : 'up';
        $migrations = $this->_configuration->getMigrationsToExecute($direction, $to);

        if (empty($migrations)) {
            $this->_printer->writeln('No migrations to execute', 'ERROR');
        }

        $sql = array();
        foreach ($migrations as $version) {
            $versionSql = $version->execute($direction, $dryRun);
            $sql[$version->getVersion()] = $versionSql;
        }

        return $sql;
    }

    protected function _announce($message)
    {
        $dash = $this->_printer->format(' == ', 'INFO');
        $this->_printer->writeln($dash . $message . $dash);
    }
}