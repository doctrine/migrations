<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;

use function chdir;
use function getcwd;
use function realpath;
use function sprintf;

/**
 * @covers \Doctrine\Migrations\Tools\Console\ConsoleRunner
 */
class ConsoleRunnerTest extends TestCase
{
    private Application $application;

    public function testCreateDependencyFactoryFromLegacyOrmHelper(): void
    {
        $dir = getcwd();
        if ($dir === false) {
            $dir = '.';
        }

        chdir(__DIR__ . '/legacy-config-orm');

        try {
            $dependencyFactory = ConsoleRunnerStub::findDependencyFactory();
            self::assertInstanceOf(DependencyFactory::class, $dependencyFactory);
            self::assertInstanceOf(SqlitePlatform::class, $dependencyFactory->getConnection()->getDatabasePlatform());
            self::assertInstanceOf(EntityManager::class, $dependencyFactory->getEntityManager());
        } finally {
            chdir($dir);
        }
    }

    /**
     * @dataProvider getDependencyFactoryTestDirectories
     */
    public function testDependencyFactory(string $directory): void
    {
        $dir = getcwd();
        if ($dir === false) {
            $dir = '.';
        }

        chdir($directory);

        try {
            $dependencyFactory = ConsoleRunnerStub::findDependencyFactory();
            self::assertInstanceOf(DependencyFactory::class, $dependencyFactory);
            self::assertSame('foo', $dependencyFactory->getConfiguration()->getCustomTemplate());
        } finally {
            chdir($dir);
        }
    }

    public function testInvalidCliConfigTriggersException(): void
    {
        $dir = getcwd();
        if ($dir === false) {
            $dir = '.';
        }

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage(sprintf(
                'Configuration file "%s" must return an instance of "%s"',
                realpath(__DIR__ . '/_wrong-config/cli-config.php'),
                DependencyFactory::class
            ));

            chdir(__DIR__ . '/_wrong-config');

            ConsoleRunnerStub::findDependencyFactory();
        } finally {
            chdir($dir);
        }
    }

    public function testNoDependencyFactoryWhenNoCliConfig(): void
    {
        $dir = getcwd();
        if ($dir === false) {
            $dir = '.';
        }

        chdir(__DIR__ . '/../');

        try {
            $dependencyFactory = ConsoleRunnerStub::findDependencyFactory();
            self::assertNull($dependencyFactory);
        } finally {
            chdir($dir);
        }
    }

    /**
     * @return array<int,array<string>>
     */
    public function getDependencyFactoryTestDirectories(): array
    {
        return [
            [__DIR__],
            [__DIR__ . '/config'],
        ];
    }

    public function testRun(): void
    {
        $application = $this->createMock(Application::class);

        ConsoleRunnerStub::$application = $application;

        $application->expects(self::once())
            ->method('run');

        ConsoleRunnerStub::run([]);
    }

    public function testHasExecuteCommand(): void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:execute'));
    }

    public function testHasGenerateCommand(): void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:generate'));
    }

    public function testHasLatestCommand(): void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:latest'));
    }

    public function testHasMigrateCommand(): void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:migrate'));
    }

    public function testHasStatusCommand(): void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:status'));
    }

    public function testHasVersionCommand(): void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:version'));
    }

    public function testHasUpToDateCommand(): void
    {
        ConsoleRunner::addCommands($this->application);

        self::assertTrue($this->application->has('migrations:up-to-date'));
    }

    public function testHasDiffCommand(): void
    {
        $dependencyFactory = $this->createMock(DependencyFactory::class);
        $dependencyFactory
            ->expects(self::atLeastOnce())
            ->method('hasSchemaProvider')
            ->willReturn(true);

        ConsoleRunner::addCommands($this->application, $dependencyFactory);

        self::assertTrue($this->application->has('migrations:diff'));
    }

    public function testNotHasDiffCommand(): void
    {
        $this->application->setHelperSet(new HelperSet([]));

        ConsoleRunner::addCommands($this->application);

        self::assertFalse($this->application->has('migrations:diff'));
    }

    public function testCreateApplication(): void
    {
        $application = ConsoleRunner::createApplication();
        $commands    = $application->all('migrations');
        self::assertCount(12, $commands);
    }

    public function testCreateApplicationWithSchemaProvider(): void
    {
        $dependencyFactory = $this->createMock(DependencyFactory::class);
        $dependencyFactory
            ->expects(self::atLeastOnce())
            ->method('hasSchemaProvider')
            ->willReturn(true);

        $application = ConsoleRunner::createApplication([], $dependencyFactory);
        $commands    = $application->all('migrations');
        self::assertCount(13, $commands);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->application = new Application();
    }
}

class ConsoleRunnerStub extends ConsoleRunner
{
    public static Application $application;

    /**
     * @param Command[] $commands
     */
    public static function createApplication(array $commands = [], ?DependencyFactory $dependencyFactory = null): Application
    {
        return static::$application;
    }
}
