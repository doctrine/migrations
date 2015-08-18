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
