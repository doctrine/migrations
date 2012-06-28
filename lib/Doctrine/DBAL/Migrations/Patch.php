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


class Patch
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
     * @return array $sql  The array of SQL queries.
     */
    public function getSql()
    {
        $this->patch(true);
    }

    /**
     * Write a migration SQL file to the given path
     *
     * @param string $path   The path to write the migration SQL file.
     * @return bool $written
     */
    public function writeSqlFile($path)
    {
        //TODO
    }

    /**
     * Run a migration to the current version or the given target version.
     *
     * @param string $to      The version to migrate to.
     * @param string $dryRun  Whether or not to make this a dry run and not execute anything.
     * @return array $sql     The array of migration sql statements
     * @throws MigrationException
     */
    public function patch($dryRun = false)
    {
        $currentVersion = (string) $this->configuration->getCurrentVersion();

        $migrations = $this->configuration->getMissingMigrations($currentVersion);

        if ($dryRun === false) {
            $this->outputWriter->write(sprintf('Executing missing migrations up to current version: <comment>%s</comment>', $currentVersion));
        } else {
            $this->outputWriter->write(sprintf('Executing dry run of missing migrations up to current version: <comment>%s</comment>', $currentVersion));
        }

        if (empty($migrations)) {
            $this->outputWriter->write('No missing migrations to patch');
            return array();
        }

        $sql = array();
        $time = 0;
        foreach ($migrations as $version) {
            $versionSql = $version->execute('up', $dryRun);
            $sql[$version->getVersion()] = $versionSql;
            $time += $version->getTime();
        }

        $this->outputWriter->write("\n  <comment>------------------------</comment>\n");
        $this->outputWriter->write(sprintf("  <info>++</info> finished in %s", $time));
        $this->outputWriter->write(sprintf("  <info>++</info> %s migrations executed", count($migrations)));
        $this->outputWriter->write(sprintf("  <info>++</info> %s sql queries", count($sql, true) - count($sql)));

        return $sql;
    }
}
