<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console;

use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * @covers \Doctrine\Migrations\Tools\Console\ConsoleRunner
 */
class ConsoleRunnerTest extends TestCase
{
    /** @var PHPUnit_Framework_MockObject_MockObject|EntityManagerHelper */
    private $entityManagerHelper;

    /** @var Application */
    private $application;

    public function testRun() : void
    {
        $helperSet = new HelperSet([]);

        $application = $this->createMock(Application::class);

        ConsoleRunnerStub::$application = $application;

        $application->expects($this->once())
            ->method('run');

        ConsoleRunnerStub::run($helperSet, []);
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
        $this->application->setHelperSet(new HelperSet([
            'em' => $this->entityManagerHelper,
        ]));

        ConsoleRunner::addCommands($this->application);

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
        $actual = ConsoleRunner::createApplication(new HelperSet());

        self::assertInstanceOf(Application::class, $actual);
    }

    protected function setUp() : void
    {
        parent::setUp();

        $this->application         = new Application();
        $this->entityManagerHelper = $this->getMockBuilder(EntityManagerHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}

class ConsoleRunnerStub extends ConsoleRunner
{
    /** @var Application|null */
    public static $application;

    /** @param AbstractCommand[] $commands */
    public static function createApplication(HelperSet $helperSet, array $commands = []) : Application
    {
        return static::$application;
    }
}
