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

use Doctrine\DBAL\Migrations\Configuration\Configuration;

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
     * @var boolean
     */
    private $noMigrationException;

    /**
     * Construct a Migration instance
     *
     * @param Configuration $configuration A migration Configuration instance
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->outputWriter = $configuration->getOutputWriter();
        $this->noMigrationException = false;
    }

    /**
     * Get the array of versions and SQL queries that would be executed for
     * each version but do not execute anything.
     *
     * @param string $to The version to migrate to.
     *
     * @return array $sql  The array of SQL queries.
     */
    public function getSql($to = null)
    {
        return $this->migrate($to, true);
    }

    /**
     * Write a migration SQL file to the given path
     *
     * @param string $path The path to write the migration SQL file.
     * @param string $to   The version to migrate to.
     *
     * @return boolean $written
     */
    public function writeSqlFile($path, $to = null)
    {
        $sql = $this->getSql($to);

        $from = $this->configuration->getCurrentVersion();
        if ($to === null) {
            $to = $this->configuration->getLatestVersion();
        }

        $direction = $from > $to ? Version::DIRECTION_DOWN : Version::DIRECTION_UP;

        $this->outputWriter->write(sprintf("# Migrating from %s to %s\n", $from, $to));

        $sqlWriter = new SqlFileWriter(
            $this->configuration->getMigrationsColumnName(),
            $this->configuration->getMigrationsTableName(),
            $path,
            $this->outputWriter
        );

        return $sqlWriter->write($sql, $direction);
    }

    /**
     * @param boolean $noMigrationException Throw an exception or not if no migration is found. Mostly for Continuous Integration.
     */
    public function setNoMigrationException($noMigrationException = false)
    {
        $this->noMigrationException = $noMigrationException;
    }

    /**
     * Run a migration to the current version or the given target version.
     *
     * @param string  $to             The version to migrate to.
     * @param boolean $dryRun         Whether or not to make this a dry run and not execute anything.
     * @param boolean $timeAllQueries Measuring or not the execution time of each SQL query.
     *
     * @return array $sql     The array of migration sql statements
     *
     * @throws MigrationException
     */
    public function migrate($to = null, $dryRun = false, $timeAllQueries = false)
    {
        /**
         * If no version to migrate to is given we default to the last available one.
         */
        if ($to === null) {
            $to = $this->configuration->getLatestVersion();
        }

        $from = (string) $this->configuration->getCurrentVersion();
        $to   = (string) $to;

        /**
         * Throw an error if we can't find the migration to migrate to in the registered
         * migrations.
         */
        $migrations = $this->configuration->getMigrations();
        if (!isset($migrations[$to]) && $to > 0) {
            throw MigrationException::unknownMigrationVersion($to);
        }

        $direction = $from > $to ? Version::DIRECTION_DOWN : Version::DIRECTION_UP;
        $migrationsToExecute = $this->configuration->getMigrationsToExecute($direction, $to);

        /**
         * If
         *  there are no migrations to execute
         *  and there are migrations,
         *  and the migration from and to are the same
         * means we are already at the destination return an empty array()
         * to signify that there is nothing left to do.
         */
        if ($from === $to && empty($migrationsToExecute) && !empty($migrations)) {
            return [];
        }

        $output = $dryRun ? 'Executing dry run of migration' : 'Migrating';
        $output .= ' <info>%s</info> to <comment>%s</comment> from <comment>%s</comment>';
        $this->outputWriter->write(sprintf($output, $direction, $to, $from));

        /**
         * If there are no migrations to execute throw an exception.
         */
        if (empty($migrationsToExecute) && !$this->noMigrationException) {
            throw MigrationException::noMigrationsToExecute();
        }

        $sql = [];
        $time = 0;
        foreach ($migrationsToExecute as $version) {
            $versionSql = $version->execute($direction, $dryRun, $timeAllQueries);
            $sql[$version->getVersion()] = $versionSql;
            $time += $version->getTime();
        }

        $this->outputWriter->write("\n  <comment>------------------------</comment>\n");
        $this->outputWriter->write(sprintf("  <info>++</info> finished in %ss", $time));
        $this->outputWriter->write(sprintf("  <info>++</info> %s migrations executed", count($migrationsToExecute)));
        $this->outputWriter->write(sprintf("  <info>++</info> %s sql queries", count($sql, true) - count($sql)));

        return $sql;
    }
}
