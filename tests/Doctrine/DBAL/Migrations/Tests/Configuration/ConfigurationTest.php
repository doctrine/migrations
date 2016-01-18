<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\MigrationException;
use Doctrine\DBAL\Migrations\OutputWriter;
use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tests\Stub\Configuration\AutoloadVersions\Version1Test;
use Doctrine\DBAL\Migrations\Tests\Stub\Configuration\AutoloadVersions\Version2Test;
use Doctrine\DBAL\Migrations\Tests\Stub\Configuration\AutoloadVersions\Version3Test;
use Doctrine\DBAL\Migrations\Tests\Stub\Configuration\AutoloadVersions\Version4Test;
use Doctrine\DBAL\Migrations\Tests\Stub\Configuration\AutoloadVersions\Version5Test;
use Doctrine\DBAL\Migrations\Version;

class ConfigurationTest extends MigrationTestCase
{
    public function testConstructorSetsOutputWriter()
    {
        $outputWriter = $this->getOutputWriterMock();

        $configuration = new Configuration(
            $this->getConnectionMock(),
            $outputWriter
        );

        $this->assertSame($outputWriter, $configuration->getOutputWriter());
    }

    public function testOutputWriterIsCreatedIfNotInjected()
    {
        $configuration = new Configuration($this->getConnectionMock());

        $this->assertInstanceOf('Doctrine\DBAL\Migrations\OutputWriter', $configuration->getOutputWriter());
    }

    public function testOutputWriterCanBeSet()
    {
        $outputWriter = $this->getOutputWriterMock();

        $configuration = new Configuration($this->getConnectionMock());
        $configuration->setOutputWriter($outputWriter);

        $this->assertSame($outputWriter, $configuration->getOutputWriter());
    }

    public function testRegisterMigrationsClassExistCheck()
    {
        $migrationsDir = __DIR__ . '/ConfigurationTestSource/Migrations';

        $configuration = new Configuration($this->getConnectionMock());
        $configuration->setMigrationsNamespace('Migrations');
        $configuration->setMigrationsDirectory($migrationsDir);

        $this->setExpectedException(
            'Doctrine\DBAL\Migrations\MigrationException',
            'Migration class "Migrations\Version123" was not found. Is it placed in "Migrations" namespace?'
        );
        $configuration->registerMigrationsFromDirectory($migrationsDir);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function getConnectionMock()
    {
        return $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|OutputWriter
     */
    private function getOutputWriterMock()
    {
        return $this->getMockBuilder('Doctrine\DBAL\Migrations\OutputWriter')
            ->disableOriginalConstructor()
            ->getMock()
        ;
    }

    public function testGetSetMigrationsColumnName()
    {
        $configuration = new Configuration($this->getConnectionMock());

        $this->assertSame('version', $configuration->getMigrationsColumnName());

        $configuration->setMigrationsColumnName('foobar');
        $this->assertSame('foobar', $configuration->getMigrationsColumnName());
    }

    /**
     * @dataProvider methodsThatNeedsVersionsLoaded
     */
    public function testVersionsTryToGetLoadedIfNotAlreadyLoadedWhenAccessingMethodThatNeedThemEvenIfNoNamespaceSet($method, $args)
    {
        $this->setExpectedException(MigrationException::class, 'Migrations namespace must be configured in order to use Doctrine migrations.');

        $configuration = new Configuration($this->getConnectionMock());
        call_user_func_array([$configuration, $method], $args);
    }

    /**
     * @dataProvider methodsThatNeedsVersionsLoaded
     */
    public function testVersionsTryToGetLoadedIfNotAlreadyLoadedWhenAccessingMethodThatNeedThemEvenIfNoDirectorySet($method, $args)
    {
        $this->setExpectedException(MigrationException::class, 'Migrations directory must be configured in order to use Doctrine migrations.');

        $configuration = new Configuration($this->getConnectionMock());
        $configuration->setMigrationsNamespace('DoctrineMigrations\\');

        call_user_func_array([$configuration, $method], $args);
    }

    /**
     * @dataProvider methodsThatNeedsVersionsLoaded
     */
    public function testVersionsTryToGetLoadedIfNotAlreadyLoadedWhenAccessingMethodThatNeedThem($method, $args, $expectedResult)
    {
        $configuration = new Configuration($this->getSqliteConnection());
        $configuration->setMigrationsNamespace(str_replace('\Version1Test', '', Version1Test::class));
        $configuration->setMigrationsDirectory(__DIR__ . '/../Stub/Configuration/AutoloadVersions');

        $result = call_user_func_array([$configuration, $method], $args);
        if ($method == 'getMigrationsToExecute') {
            $result = array_keys($result);
        }
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * @dataProvider methodsThatNeedsVersionsLoadedWithAlreadyMigratedMigrations
     */
    public function testVersionsTryToGetLoadedIfNotAlreadyLoadedWhenAccessingMethodThatNeedThemEvenIfSomeMigrationsAreAlreadyMigrated($method, $args, $expectedResult)
    {
        $configuration = new Configuration($this->getSqliteConnection());
        $configuration->setMigrationsNamespace(str_replace('\Version1Test', '', Version1Test::class));
        $configuration->setMigrationsDirectory(__DIR__ . '/../Stub/Configuration/AutoloadVersions');
        $migration = new Migration($configuration);
        $migration->migrate('3Test');

        $result = call_user_func_array([$configuration, $method], $args);
        if ($method == 'getMigrationsToExecute') {
            $result = array_keys($result);
        }
        $this->assertEquals($expectedResult, $result);
    }

    public function methodsThatNeedsVersionsLoadedWithAlreadyMigratedMigrations()
    {
        return [
            ['hasVersion', ['4Test'], true],
            ['getAvailableVersions', [], ['1Test', '2Test', '3Test', '4Test', '5Test']],
            ['getCurrentVersion', [], '3Test'],
            ['getRelativeVersion', ['3Test', -1], '2Test'],
            ['getNumberOfAvailableMigrations', [], 5],
            ['getLatestVersion', [], '5Test'],
            ['getMigrationsToExecute', ['up', 5], [
                '4Test',
                '5Test',
            ]],
            ['getMigrationsToExecute', ['up', 4], [
                '4Test',
            ]],
            ['getMigrationsToExecute', ['down', 0], [
                '3Test',
                '2Test',
                '1Test',
            ]],
            ['getMigrationsToExecute', ['down', 2], [
                '3Test',
            ]],
        ];
    }

    public function methodsThatNeedsVersionsLoaded()
    {
        return [
            ['hasVersion', ['3Test'], true],
            ['getAvailableVersions', [], ['1Test', '2Test', '3Test', '4Test', '5Test']],
            ['getCurrentVersion', [], '0'],
            ['getRelativeVersion', ['3Test', -1], '2Test'],
            ['getNumberOfAvailableMigrations', [], 5],
            ['getLatestVersion', [], '5Test'],
            ['getMigrationsToExecute', ['up', 5], [
                '1Test',
                '2Test',
                '3Test',
                '4Test',
                '5Test',
            ]],
            ['getMigrationsToExecute', ['down', 0], []],
            ['getMigrationsToExecute', ['down', 2], []],
        ];
    }
}
