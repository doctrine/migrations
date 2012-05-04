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

use Doctrine\DBAL\Migrations\Configuration\Configuration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Class for running migrations to the current version or a manually specified version.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Migration
{
    /**
     * The OutputWriter object instance used for outputting information
     *
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Construct a Migration instance
     *
     * @param Configuration $configuration  A migration Configuration instance
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->outputWriter = $configuration->getOutputWriter();
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

        $from = $this->configuration->getCurrentVersion();
        if ($to === null) {
            $to = $this->configuration->getLatestVersion();
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
            $path = $path . '/doctrine_migration_' . date('YmdHis') . '.sql';
        }

        $this->outputWriter->write("\n".sprintf('Writing migration file to "<info>%s</info>"', $path));

        return file_put_contents($path, $string);
    }

    /**
     * Run a migration to the current version or the given target version.
     *
     * @param string $to      The version to migrate to.
     * @param string $dryRun  Whether or not to make this a dry run and not execute anything.
     * @return array $sql     The array of migration sql statements
     * @throws MigrationException
     */
    public function migrate($to = null, $dryRun = false)
    {
        if ($to === null) {
            $to = $this->configuration->getLatestVersion();
        }

        $from = $this->configuration->getCurrentVersion();
        $from = (string) $from;
        $to = (string) $to;

        $migrations = $this->configuration->getMigrations();
        if ( ! isset($migrations[$to]) && $to > 0) {
            throw MigrationException::unknownMigrationVersion($to);
        }

        $direction = $from > $to ? 'down' : 'up';
        $migrationsToExecute = $this->configuration->getMigrationsToExecute($direction, $to);

        if ($from === $to && empty($migrationsToExecute) && $migrations) {
            return array();
        }

        if ($dryRun === false) {
            $this->outputWriter->write(sprintf('Migrating <info>%s</info> to <comment>%s</comment> from <comment>%s</comment>', $direction, $to, $from));
        } else {
            $this->outputWriter->write(sprintf('Executing dry run of migration <info>%s</info> to <comment>%s</comment> from <comment>%s</comment>', $direction, $to, $from));
        }

        if (empty($migrationsToExecute)) {
            throw MigrationException::noMigrationsToExecute();
        }

        $sql = array();
        $time = 0;
        foreach ($migrationsToExecute as $version) {
            $versionSql = $version->execute($direction, $dryRun);
            $sql[$version->getVersion()] = $versionSql;
            $time += $version->getTime();
        }

        $this->outputWriter->write("\n  <comment>------------------------</comment>\n");
        $this->outputWriter->write(sprintf("  <info>++</info> finished in %s", $time));
        $this->outputWriter->write(sprintf("  <info>++</info> %s migrations executed", count($migrationsToExecute)));
        $this->outputWriter->write(sprintf("  <info>++</info> %s sql queries", count($sql, true) - count($sql)));

        return $sql;
    }
}
