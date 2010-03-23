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

use Doctrine\DBAL\Schema\Schema,
    DoctrineExtensions\Migrations\Configuration\Configuration,
    DoctrineExtensions\Migrations\Version;

/**
 * Abstract class for all users migration classes to extend from.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       2.0
 * @version     $Revision$
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class AbstractMigration
{
    /** The Doctrine\DBAL\Connection instance we are migrating */
    protected $_connection;

    /** Reference to the SchemaManager instance referened by $_connection */
    protected $_sm;

    /** Reference to the DatabasePlatform instance referenced by $_conection */
    protected $_platform;

    /** CLI Printer instance used for useful output about your migration */
    protected $_printer;

    /** Reference to the Version instance representing this migration */
    protected $_version;

    public function __construct(Version $version)
    {
        $configuration = $version->getConfiguration();
        $this->_connection = $configuration->getConnection();
        $this->_sm = $this->_connection->getSchemaManager();
        $this->_platform = $this->_connection->getDatabasePlatform();
        $this->_printer = $configuration->getPrinter();
        $this->_version = $version;
    }

    abstract public function up(Schema $schema);
    abstract public function down(Schema $schema);

    protected function _announce($message)
    {
        $this->_version->announce($message);
    }

    protected function _addSchemaChangesSql(Schema $schema)
    {
        return $this->_version->migrateSchema($schema);
    }

    protected function _addSql($sql)
    {
        return $this->_version->addSql($sql);
    }

    protected function _throwIrreversibleMigrationException($msg = null)
    {
        throw new IrreversibleMigrationException($msg);
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