<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests\Tools\Console\Command;

use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\Tools\Console\Command\UpToDateCommand;
use Doctrine\Migrations\Version\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;

class UpToDateCommandTest extends TestCase
{
    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var UpToDateCommand */
    private $upToDateCommand;

    protected function setUp() : void
    {
        $this->migrationRepository = $this->createMock(MigrationRepository::class);

        $this->upToDateCommand = new UpToDateCommand();
        $this->upToDateCommand->setMigrationRepository($this->migrationRepository);
    }

    /**
     * @param Version[] $migrations
     * @param string[]  $migratedVersions
     *
     * @dataProvider dataIsUpToDate
     */
    public function testIsUpToDate(array $migrations, array $migratedVersions, int $exitCode) : void
    {
        $this->migrationRepository
            ->method('getMigrations')
            ->willReturn($migrations);

        $this->migrationRepository
            ->method('getMigratedVersions')
            ->willReturn($migratedVersions);

        $output = $this->createMock(OutputInterface::class);

        $output->expects(self::once())
            ->method('writeln');

        $actual = $this->upToDateCommand->execute(new ArrayInput([]), $output);

        self::assertSame($exitCode, $actual);
    }

    /**
     * @return string[][]
     */
    public function dataIsUpToDate() : array
    {
        return [
            'up-to-date' => [
                [
                    $this->createVersion('20160614015627'),
                ],
                ['20160614015627'],
                0,
            ],
            'empty-migration-set' => [
                [],
                [],
                0,
            ],
            'one-migration-available' => [
                [
                    $this->createVersion('20150614015627'),
                ],
                [],
                1,
            ],
            'many-migrations-available' => [
                [
                    $this->createVersion('20110614015627'),
                    $this->createVersion('20120614015627'),
                    $this->createVersion('20130614015627'),
                    $this->createVersion('20140614015627'),
                ],
                ['20110614015627'],
                1,
            ],
        ];
    }

    private function createVersion(string $migration) : Version
    {
        $version = $this->createMock(Version::class);

        $version->method('getVersion')
            ->willReturn($migration);

        return $version;
    }
}
