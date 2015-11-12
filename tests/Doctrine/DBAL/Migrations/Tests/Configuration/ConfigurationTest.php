<?php

namespace Doctrine\DBAL\Migrations\Tests\Configuration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\OutputWriter;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
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
}
