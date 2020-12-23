<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use DateTimeImmutable;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\Migrations\Tools\Console\Helper\MigrationStatusInfosHelper;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommandTest extends TestCase
{
    /** @var Configuration|MockObject */
    private $configuration;

    /** @var DependencyFactory|MockObject */
    private $dependencyFactory;

    /** @var MigrationRepository|MockObject */
    private $migrationRepository;

    /** @var MigrationStatusInfosHelper|MockObject */
    private $migrationStatusInfosHelper;

    /** @var StatusCommand */
    private $statusCommand;

    public function testExecute(): void
    {
        $input  = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $this->migrationStatusInfosHelper->expects(self::once())
            ->method('getMigrationsInfos')
            ->willReturn(['name' => 'value']);

        $input->expects(self::once())
            ->method('getOption')
            ->with('show-versions')
            ->willReturn(true);

        $version1   = $this->createMock(Version::class);
        $migration1 = $this->createMock(AbstractMigration::class);

        $version1->expects(self::once())
            ->method('getVersion')
            ->willReturn('1');

        $version1->expects(self::once())
            ->method('getMigration')
            ->willReturn($migration1);

        $version1->expects(self::once())
            ->method('isMigrated')
            ->willReturn(true);

        $executedAt = new DateTimeImmutable('2018-05-16 11:14:40');

        $version1->expects(self::once())
            ->method('getExecutedAt')
            ->willReturn($executedAt);

        $migration1->expects(self::once())
            ->method('getDescription')
            ->willReturn('Test description.');

        $version2   = $this->createMock(Version::class);
        $migration2 = $this->createMock(AbstractMigration::class);

        $version2->expects(self::once())
            ->method('getVersion')
            ->willReturn('2');

        $version2->expects(self::once())
            ->method('getMigration')
            ->willReturn($migration2);

        $version2->expects(self::once())
            ->method('isMigrated')
            ->willReturn(false);

        $version2->expects(self::once())
            ->method('getExecutedAt')
            ->willReturn(null);

        $migration2->expects(self::once())
            ->method('getDescription')
            ->willReturn('Test description.');

        $this->migrationRepository->expects(self::once())
            ->method('getMigrations')
            ->willReturn([$version1, $version2]);

        $this->migrationRepository->expects(self::once())
            ->method('getExecutedUnavailableMigrations')
            ->willReturn(['1234']);

        $this->configuration->expects(self::at(0))
            ->method('getDateTime')
            ->with('1234')
            ->willReturn('123456789');

        $output->expects(self::at(0))
            ->method('writeln')
            ->with("\n <info>==</info> Configuration\n");

        $output->expects(self::at(1))
            ->method('writeln')
            ->with('    <comment>>></comment> name:                                               value');

        $output->expects(self::at(2))
            ->method('writeln')
            ->with("\n <info>==</info> Available Migration Versions\n");

        $output->expects(self::at(3))
            ->method('writeln')
            ->with('    <comment>>></comment>  (<comment>1</comment>)                                                <info>migrated</info> (executed at 2018-05-16 11:14:40)     Test description.');

        $output->expects(self::at(4))
            ->method('writeln')
            ->with('    <comment>>></comment>  (<comment>2</comment>)                                                <error>not migrated</error>     Test description.');

        $output->expects(self::at(5))
            ->method('writeln')
            ->with("\n <info>==</info> Previously Executed Unavailable Migration Versions\n");

        $output->expects(self::at(6))
            ->method('writeln')
            ->with('    <comment>>></comment> 123456789 (<comment>1234</comment>)');

        $this->statusCommand->execute($input, $output);
    }

    protected function setUp(): void
    {
        $this->configuration              = $this->createMock(Configuration::class);
        $this->dependencyFactory          = $this->createMock(DependencyFactory::class);
        $this->migrationRepository        = $this->createMock(MigrationRepository::class);
        $this->migrationStatusInfosHelper = $this->createMock(MigrationStatusInfosHelper::class);

        $this->dependencyFactory->expects(self::once())
            ->method('getMigrationStatusInfosHelper')
            ->willReturn($this->migrationStatusInfosHelper);

        $this->statusCommand = new StatusCommand();
        $this->statusCommand->setMigrationConfiguration($this->configuration);
        $this->statusCommand->setDependencyFactory($this->dependencyFactory);
        $this->statusCommand->setMigrationRepository($this->migrationRepository);
    }
}
