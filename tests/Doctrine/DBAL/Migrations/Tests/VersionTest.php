<?php
namespace Doctrine\DBAL\Migrations\Tests;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Migrations\Configuration\Configuration;

/**
 * Version Test
 */
class VersionTest extends MigrationTestCase
{
    /**
     * Create simple migration
     */
    public function testCreateVersion()
    {
        $version = new Version(new Configuration($this->getSqliteConnection()), $versionName = '003',
            'Doctrine\DBAL\Migrations\Tests\VersionTest_Migration');
        $this->assertEquals($versionName, $version->getVersion());
    }

    /**
     * Create migration with description
     */
    public function testCreateVersionWithCustomName()
    {
        $versionName = '003';
        $versionDescription = 'My super migration';
        $version = new Version(new Configuration($this->getSqliteConnection()), $versionName,
            'Doctrine\DBAL\Migrations\Tests\VersionTest_MigrationDescription');
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
