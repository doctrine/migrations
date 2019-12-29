<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @covers \Doctrine\Migrations\Tools\Console\ConsoleRunner
 */
class ConsoleRunnerTest extends TestCase
{
    /** @var Application */
    private $application;

    public function testRun() : void
    {
        $application = $this->createMock(Application::class);

        ConsoleRunnerStub::$application = $application;

        $application->expects(self::once())
            ->method('run');

        ConsoleRunnerStub::run([]);
    }

    public function testHasExecuteCommand() : void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:execute'));
    }

    public function testHasGenerateCommand() : void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:generate'));
    }

    public function testHasLatestCommand() : void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:latest'));
    }

    public function testHasMigrateCommand() : void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:migrate'));
    }

    public function testHasStatusCommand() : void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:status'));
    }

    public function testHasVersionCommand() : void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:version'));
    }

    public function testHasUpToDateCommand() : void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:up-to-date'));
    }

    public function testHasDiffCommand() : void
    {
        $em = $this->createMock(EntityManager::class);

        $dependencyFactory = $this->createMock(DependencyFactory::class);
        $dependencyFactory
            ->expects(self::atLeastOnce())
            ->method('getEntityManager')
            ->willReturn($em);

        ConsoleRunner::addCommands($this->application, $dependencyFactory);

        self::assertTrue($this->application->has('migrations:diff'));
    }

    public function testNotHasDiffCommand() : void
    {
        $this->application->setHelperSet(new HelperSet([]));

        ConsoleRunner::addCommands($this->application);

        self::assertFalse($this->application->has('migrations:diff'));
    }

    public function testCreateApplication() : void
    {
        $application = ConsoleRunner::createApplication();
        $commands    = $application->all('migrations');
        self::assertCount(9, $commands);
    }

    public function testCreateApplicationWithEntityManager() : void
    {
        $em = $this->createMock(EntityManager::class);

        $dependencyFactory = $this->createMock(DependencyFactory::class);
        $dependencyFactory
            ->expects(self::atLeastOnce())
            ->method('getEntityManager')
            ->willReturn($em);

        $application = ConsoleRunner::createApplication([], $dependencyFactory);
        $commands    = $application->all('migrations');
        self::assertCount(10, $commands);
    }

    protected function setUp() : void
    {
        parent::setUp();
        $this->application = new Application();
    }
}

class ConsoleRunnerStub extends ConsoleRunner
{
    /** @var Application */
    public static $application;

    /**
     * @param Command[] $commands
     */
    public static function createApplication(array $commands = [], ?DependencyFactory $dependencyFactory = null) : Application
    {
        return static::$application;
    }
}
