<?php

use Doctrine\DBAL\Migrations\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @covers \Doctrine\DBAL\Migrations\Tools\Console\ConsoleRunner
 */
class ConsoleRunnerTest extends \PHPUnit_Framework_TestCase
{
    /** @var  PHPUnit_Framework_MockObject_MockObject|EntityManagerHelper */
    private $entityManagerHelper;
    
    /** @var Application */
    private $application;

    protected function setUp()
    {
        parent::setUp();
        
        $this->application = new Application();
        $this->entityManagerHelper = $this->getMockBuilder(EntityManagerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testHasExecuteCommand()
    {
        ConsoleRunner::addCommands($this->application);
        
        self::assertTrue($this->application->has('migrations:execute'));
    }

    public function testHasGenerateCommand()
    {
        ConsoleRunner::addCommands($this->application);
        
        self::assertTrue($this->application->has('migrations:generate'));
    }

    public function testHasLatestCommand()
    {
        ConsoleRunner::addCommands($this->application);
        
        self::assertTrue($this->application->has('migrations:latest'));
    }

    public function testHasMigrateCommand()
    {
        ConsoleRunner::addCommands($this->application);
        
        self::assertTrue($this->application->has('migrations:migrate'));
    }

    public function testHasStatusCommand()
    {
        ConsoleRunner::addCommands($this->application);
        
        self::assertTrue($this->application->has('migrations:status'));
    }

    public function testHasVersionCommand()
    {
        ConsoleRunner::addCommands($this->application);
        
        self::assertTrue($this->application->has('migrations:version'));
    }

    public function testHasUpToDateCommand()
    {
        ConsoleRunner::addCommands($this->application);
        
        self::assertTrue($this->application->has('migrations:up-to-date'));
    }

    public function testHasDiffCommand()
    {
        $this->application->setHelperSet(new HelperSet(array(
            'em' => $this->entityManagerHelper,
        )));
        
        ConsoleRunner::addCommands($this->application);
        
        self::assertTrue($this->application->has('migrations:diff'));
    }

    public function testNotHasDiffCommand()
    {
        $this->application->setHelperSet(new HelperSet(array(
            
        )));

        ConsoleRunner::addCommands($this->application);
        
        self::assertFalse($this->application->has('migrations:diff'));
    }
    
    public function testCreateApplication()
    {
        $actual = ConsoleRunner::createApplication(new HelperSet());
        
        self::assertInstanceOf(Application::class, $actual);
    }
}
