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

namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use \Mockery as m;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class VersionTest extends MigrationTestCase
{
    /**
     * Create simple migration
     */
    public function testCreateVersion()
    {
        $versionName = '003';
        $version = new Version(
            new Configuration($this->getSqliteConnection()),
            $versionName,
            'Doctrine\DBAL\Migrations\Tests\VersionTest_Migration'
        );

        $this->assertEquals($versionName, $version->getVersion());
    }

    /**
     * Create migration with description
     */
    public function testCreateVersionWithCustomName()
    {
        $versionName = '003';
        $versionDescription = 'My super migration';
        $version = new Version(
            new Configuration($this->getSqliteConnection()),
            $versionName,
            'Doctrine\DBAL\Migrations\Tests\VersionTest_MigrationDescription'
        );

        $this->assertEquals($versionName, $version->getVersion());
        $this->assertEquals($versionDescription, $version->getMigration()->getDescription());
    }

    /**
     * Test outputQueryTime on all queries
     */
    public function testOutputQueryTimeAllQueries()
    {
        $outputWriterMock = $this->getMock('Doctrine\DBAL\Migrations\OutputWriter');
        $outputWriterMock->expects($this->once())->method('write');
        $configuration = new Configuration($this->getSqliteConnection(), $outputWriterMock);
        $reflectionVersion = new \ReflectionClass('Doctrine\DBAL\Migrations\Version');
        $method = $reflectionVersion->getMethod('outputQueryTime');
        $method->setAccessible(true);

        $version = new Version(
            $configuration,
            $versionName = '003',
            'Doctrine\DBAL\Migrations\Tests\VersionTest_Migration'
        );
        $this->assertNull($method->invoke($version, 0, true));
    }

    /**
     * Test outputQueryTime not on all queries
     */
    public function testOutputQueryTimeNotAllQueries()
    {
        $outputWriterMock = $this->getMock('Doctrine\DBAL\Migrations\OutputWriter');
        $outputWriterMock->expects($this->exactly(0))->method('write');
        $configuration = new Configuration($this->getSqliteConnection(), $outputWriterMock);
        $reflectionVersion = new \ReflectionClass('Doctrine\DBAL\Migrations\Version');
        $method = $reflectionVersion->getMethod('outputQueryTime');
        $method->setAccessible(true);

        $version = new Version(
            $configuration,
            $versionName = '003',
            'Doctrine\DBAL\Migrations\Tests\VersionTest_Migration'
        );
        $this->assertNull($method->invoke($version, 0, false));
    }

    /**
     * Test outputQueryTime not on all queries
     * @dataProvider stateProvider
     */
    public function testGetExecutionState($state)
    {
        $configuration = new Configuration($this->getSqliteConnection());
        $version = new Version(
            $configuration,
            $versionName = '003',
            'Doctrine\DBAL\Migrations\Tests\VersionTest_Migration'
        );
        $reflectionVersion = new \ReflectionClass('Doctrine\DBAL\Migrations\Version');
        $stateProperty = $reflectionVersion->getProperty('state');
        $stateProperty->setAccessible(true);
        $stateProperty->setValue($version, $state);
        $this->assertNotEmpty($version->getExecutionState());
    }

    /**
     * Provides states
     * @return array
     */
    public function stateProvider()
    {
        return array(
            array(Version::STATE_NONE),
            array(Version::STATE_EXEC),
            array(Version::STATE_POST),
            array(Version::STATE_PRE),
            array(-1),
        );
    }

    /**
     * Test add sql
     */
    public function testAddSql()
    {
        $configuration = new Configuration($this->getSqliteConnection());
        $version = new Version(
            $configuration,
            $versionName = '003',
            'Doctrine\DBAL\Migrations\Tests\VersionTest_Migration'
        );
        $this->assertNull($version->addSql('SELECT * FROM foo'));
        $this->assertNull($version->addSql(array('SELECT * FROM foo')));
        $this->assertNull($version->addSql(array('SELECT * FROM foo WHERE id = ?'), array(1)));
        $this->assertNull($version->addSql(array('SELECT * FROM foo WHERE id = ?'), array(1), array(\PDO::PARAM_INT)));
    }

    /**
     * @param $path
     * @param $to
     * @param $getSqlReturn
     *
     * @dataProvider writeSqlFileProvider
     */
    public function testWriteSqlFile($path, $direction, $getSqlReturn)
    {
        $version = 1;

        $outputWriter = m::mock('Doctrine\DBAL\Migrations\OutputWriter');
        $outputWriter->shouldReceive('write');

        $connection = m::mock('Doctrine\DBAL\Connection');
        $connection->shouldReceive([
            'getSchemaManager' => 'something',
            'getDatabasePlatform' => 'something else',
        ]);

        $config = m::mock('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->makePartial();
        $config->shouldReceive('getOutputWriter')->andReturn($outputWriter);
        $config->shouldReceive('getConnection')->andReturn($connection);

        $migration = m::mock('Doctrine\DBAL\Migrations\Version[execute]', [$config, $version, 'stdClass'])->makePartial();
        $migration->shouldReceive('execute')->with($direction, true)->andReturn($getSqlReturn);

        $expectedReturn = 123;
        $sqlWriter = m::instanceMock('overload:Doctrine\DBAL\Migrations\SqlFileWriter');
        $sqlWriter->shouldReceive('write')->with(m::type('array'), m::anyOf('up', 'down'))->andReturn($expectedReturn);

        $result = $migration->writeSqlFile($path, $direction);
        $this->assertEquals($expectedReturn, $result);
    }

    public function writeSqlFileProvider()
    {
        return [
            [__DIR__, 'up', ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__, 'down', ['1' => ['SHOW DATABASES;']]], // up
            [__DIR__ . '/tmpfile.sql', 'up', ['1' => ['SHOW DATABASES']]], // tests something actually got written
        ];
    }
}

/**
 * Simple migration
 */
class VersionTest_Migration extends AbstractMigration
{
    public function down(Schema $schema) {}
    public function up(Schema $schema)   {}
}

/**
 * Migration with description
 */
class VersionTest_MigrationDescription extends AbstractMigration
{
    public function getDescription()
    {
        return 'My super migration';
    }

    public function down(Schema $schema) {}
    public function up(Schema $schema)   {}
}
