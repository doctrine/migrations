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

use Doctrine\DBAL\Schema\Schema,
    Doctrine\DBAL\Migrations\Configuration\Configuration,
    Doctrine\DBAL\Migrations\Version;

/**
 * Abstract class for individual migrations to extend from.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class AbstractMigration
{
    /**
     * The Migrations Configuration instance for this migration
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * The OutputWriter object instance used for outputting information
     *
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * The Doctrine\DBAL\Connection instance we are migrating
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $connection;

    /**
     * Reference to the SchemaManager instance referened by $_connection
     *
     * @var \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    protected $sm;

    /**
     * Reference to the DatabasePlatform instance referenced by $_conection
     *
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $platform;

    /**
     * Reference to the Version instance representing this migration
     *
     * @var Version
     */
    protected $version;

    public function __construct(Version $version)
    {
        $this->configuration = $version->getConfiguration();
        $this->outputWriter = $this->configuration->getOutputWriter();
        $this->connection = $this->configuration->getConnection();
        $this->sm = $this->connection->getSchemaManager();
        $this->platform = $this->connection->getDatabasePlatform();
        $this->version = $version;
    }

    /**
     * Get custom migration name
     *
     * @return string
     */
    public function getName()
    {
    }

    abstract public function up(Schema $schema);
    abstract public function down(Schema $schema);

    protected function addSql($sql, array $params = array(), array $types = array())
    {
        $this->version->addSql($sql, $params, $types);
    }

    protected function write($message)
    {
        $this->outputWriter->write($message);
    }

    protected function throwIrreversibleMigrationException($message = null)
    {
        if ($message === null) {
            $message = 'This migration is irreversible and cannot be reverted.';
        }
        throw new IrreversibleMigrationException($message);
    }

    /**
     * Print a warning message if the condition evalutes to TRUE.
     *
     * @param boolean $condition
     * @param string  $message
     */
    public function warnIf($condition, $message = '')
    {
        $message = (strlen($message)) ? $message : 'Unknown Reason';

        if ($condition === true) {
            $this->outputWriter->write('    <warning>Warning during ' . $this->version->getExecutionState() . ': ' . $message . '</warning>');
        }
    }

    /**
     * Abort the migration if the condition evalutes to TRUE.
     *
     * @param boolean $condition
     * @param string  $message
     *
     * @throws AbortMigrationException
     */
    public function abortIf($condition, $message = '')
    {
        $message = (strlen($message)) ? $message : 'Unknown Reason';

        if ($condition === true) {
            throw new AbortMigrationException($message);
        }
    }

    /**
     * Skip this migration (but not the next ones) if condition evalutes to TRUE.
     *
     * @param boolean $condition
     * @param string  $message
     *
     * @throws SkipMigrationException
     */
    public function skipIf($condition, $message = '')
    {
        $message = (strlen($message)) ? $message : 'Unknown Reason';

        if ($condition === true) {
            throw new SkipMigrationException($message);
        }
    }

    public function preUp(Schema $schema)
    {
    }

    public function postUp(Schema $schema)
    {
    }

    public function preDown(Schema $schema)
    {
    }

    public function postDown(Schema $schema)
    {
    }
}
