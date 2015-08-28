<?php

namespace Doctrine\DBAL\Migrations\Tests\Tools\Console\Command;

use Doctrine\DBAL\Migrations\Tests\MigrationTestCase;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateCommandTest extends MigrationTestCase
{

    public function testGetVersionNameFromAlias()
    {
        $class = new \ReflectionClass('Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand');
        $method = $class->getMethod('getVersionNameFromAlias');
        $method->setAccessible(true);

        $configuration = $this->getConfigurationMock(['resolveVersionAlias']);

        $output = $this->getOutputStream();

        $this->assertEquals(false, $method->invokeArgs(new MigrateCommand(), ['prev', $output, $configuration]));
        $this->assertContains('Already at first version.', $this->getOutputStreamContent($output));

        $output = $this->getOutputStream();

        $this->assertEquals(false, $method->invokeArgs(new MigrateCommand(), ['next', $output, $configuration]));
        $this->assertContains('Already at latest version.', $this->getOutputStreamContent($output));

        $output = $this->getOutputStream();

        $this->assertEquals(false, $method->invokeArgs(new MigrateCommand(), ['giberich', $output, $configuration]));
        $this->assertContains('Unknown version: giberich', $this->getOutputStreamContent($output));

        $output = $this->getOutputStream();

        $configuration
            ->expects($this->once())
            ->method('resolveVersionAlias')
            ->will($this->returnValue('1234'));

        $this->assertEquals('1234', $method->invokeArgs(new MigrateCommand(), ['test', $output, $configuration]));
        $this->assertEquals('', $this->getOutputStreamContent($output));
    }

    public function testCanExecute()
    {
        if (!class_exists('Symfony\Component\Console\Helper\QuestionHelper')) {
            $this->markTestSkipped(
                'The QuestionHelper must be available.'
            );
        }

        $input = $this->getInputMock(true);

        $output = $this->getOutputStream();

        $class = new \ReflectionClass('Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand');
        $method = $class->getMethod('canExecute');
        $method->setAccessible(true);

        /** @var \Doctrine\DBAL\Migrations\Tools\Console\Command\AbstractCommand $command */
        $command = $this->getMock(
            'Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand',
            ['getHelperSet']
        );

        $helper = new QuestionHelper();
        $helper->setInputStream($this->getInputStream("y\n"));
        if ($helper instanceof QuestionHelper) {
            $helperSet = new HelperSet([
                'question' => $helper
            ]);
        }
        $command->setHelperSet($helperSet);
        $command->expects($this->any())
            ->method('getHelperSet')
            ->will($this->returnValue($helperSet));

        //should return true if user confirm
        $this->assertEquals(true, $method->invokeArgs($command, ['test', $input, $output]));

        //shoudl return false if user cancel
        $helper->setInputStream($this->getInputStream("n\n"));
        $this->assertEquals(false, $method->invokeArgs($command, ['test', $input, $output]));

        //should return true if non interactive
        $input = $this->getInputMock(false);
        $this->assertEquals(true, $method->invokeArgs($command, ['test', $input, $output]));
    }


    public function testExecuteReturn1WhenUnknowVersion()
    {
        $input = $this->getInputMock(false);
        $output = $this->getOutputStream();

        $class = new \ReflectionClass('Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand');
        $method = $class->getMethod('execute');
        $method->setAccessible(true);


        $configuration = $this->getConfigurationMock(['getConnection', 'getMigratedVersions', 'getAvailableVersions']);
        $configuration
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->getSqliteConnection());
        $configuration
            ->expects($this->any())
            ->method('getMigratedVersions')
            ->willReturn([]);
        $configuration
            ->expects($this->any())
            ->method('getAvailableVersions')
            ->willReturn([]);


        /** @var \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand $command */
        $command = $this->getMockBuilder('Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand')
            ->disableOriginalConstructor()
            ->setMethods([
                'getHelperSet',
                'getMigrationConfiguration',
                'getVersionNameFromAlias',
                'getMigratedVersions',
            ])
            ->getMock();

        $command->expects($this->any())
            ->method('getHelperSet')
            ->willReturn(new HelperSet([
                'question' => new QuestionHelper(),
                'connection' => new ConnectionHelper($this->getSqliteConnection()),
            ]));

        $command->expects($this->any())
            ->method('getHelper')
            ->with('connection')
            ->willReturn(new ConnectionHelper($this->getSqliteConnection()));

        $command->expects($this->any())
            ->method('getMigrationConfiguration')
            ->willReturn($configuration);

        $command->expects($this->any())
            ->method('getVersionNameFromAlias')
            ->willReturn(false);

        $command->expects($this->any())
            ->method('getMigratedVersions')
            ->willReturn(array());

        $this->assertEquals(1, $method->invokeArgs($command, [$input, $output]));
        $this->assertContains('Unknown version:', $this->getOutputStreamContent($output));
    }

    public function testExecuteReturnShowMessageWhenExecutedMigrationAreUnavailable()
    {
        $input = $this->getInputMock(true);
        $output = $this->getOutputStream();

        $class = new \ReflectionClass('Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand');
        $method = $class->getMethod('execute');
        $method->setAccessible(true);


        $configuration = $this->getConfigurationMock(['getConnection', 'getMigratedVersions', 'getAvailableVersions']);
        $configuration
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->getSqliteConnection());
        $configuration
            ->expects($this->any())
            ->method('getMigratedVersions')
            ->willReturn(['1','2', '3']);
        $configuration
            ->expects($this->any())
            ->method('getAvailableVersions')
            ->willReturn(['tralala']);


        /** @var \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand $command */
        $command = $this->getMockBuilder('Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand')
            ->disableOriginalConstructor()
            ->setMethods([
                'getHelper',
                'getHelperSet',
                'getMigrationConfiguration',
                'getVersionNameFromAlias',
            ])
            ->getMock();

        $command->expects($this->any())
            ->method('getHelperSet')
            ->willReturn(new HelperSet([
                'question' => new QuestionHelper(),
                'connection' => new ConnectionHelper($this->getSqliteConnection()),
            ]));

        $command->expects($this->any())
            ->method('getHelper')
            ->with('connection')
            ->willReturn(new ConnectionHelper($this->getSqliteConnection()));

        $command->expects($this->any())
            ->method('getMigrationConfiguration')
            ->willReturn($configuration);

        $command->expects($this->any())
            ->method('getVersionNameFromAlias')
            ->willReturn('tralala');

        $helper = new QuestionHelper();
        $helper->setInputStream($this->getInputStream("n\n"));
        if ($helper instanceof QuestionHelper) {
            $helperSet = new HelperSet([
                'question' => $helper
            ]);
        }
        $command->setHelperSet($helperSet);

        $this->assertEquals(1, $method->invokeArgs($command, [$input, $output]));
        $this->assertContains('previously executed migrations'
            . ' in the database that are not registered migrations.', $this->getOutputStreamContent($output));
    }

    private function getInputMock($isInteractive)
    {
        $input = $this->getMockBuilder('Symfony\Component\Console\Input\ArrayInput')
            ->setConstructorArgs([[]])
            ->setMethods(['isInteractive', 'getOption', 'getArgument'])
            ->getMock();

        $input->expects($this->any())
            ->method('isInteractive')
            ->will($this->returnValue($isInteractive));

        $input->expects($this->any())
            ->method('getOption')
            ->with($this->logicalOr(
                'db-configuration',
                'configuration',
                'query-time'
            ))
            ->will($this->returnValue(false));

        $input->expects($this->any())
            ->method('getArgument')
            ->willReturn('tralala');

        return $input;
    }

    private function getConfigurationMock($mockedMethods)
    {
        $configuration = $this->getMockBuilder('Doctrine\DBAL\Migrations\Configuration\Configuration')
            ->setConstructorArgs([$this->getSqliteConnection()])
            ->setMethods($mockedMethods)
            ->getMock();

        return $configuration;
    }
}
