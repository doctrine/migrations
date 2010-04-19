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
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class AbstractMigration
{
    /** The Migrations Configuration instance for this migration */
    protected $_configuration;

    /** The OutputWriter object instance used for outputting information */
    protected $_outputWriter;

    /** The Doctrine\DBAL\Connection instance we are migrating */
    protected $_connection;

    /** Reference to the SchemaManager instance referened by $_connection */
    protected $_sm;

    /** Reference to the DatabasePlatform instance referenced by $_conection */
    protected $_platform;

    /** Reference to the Version instance representing this migration */
    protected $_version;

    public function __construct(Version $version)
    {
        $this->_configuration = $version->getConfiguration();
        $this->_outputWriter = $this->_configuration->getOutputWriter();
        $this->_connection = $this->_configuration->getConnection();
        $this->_sm = $this->_connection->getSchemaManager();
        $this->_platform = $this->_connection->getDatabasePlatform();
        $this->_version = $version;
    }

    abstract public function up(Schema $schema);
    abstract public function down(Schema $schema);

    protected function _addSql($sql)
    {
        return $this->_version->addSql($sql);
    }

    protected function _write($message)
    {
        $this->_outputWriter->write($message);
    }

    protected function _throwIrreversibleMigrationException($message = null)
    {
        if ($message === null) {
            $message = 'This migration is irreversible and cannot be reverted.';
        }
        throw new IrreversibleMigrationException($message);
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