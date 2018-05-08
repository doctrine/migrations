<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\Migrations\MigrationRepository;
use Doctrine\Migrations\VersionAliasResolver;
use PHPUnit\Framework\TestCase;

final class VersionAliasResolverTest extends TestCase
{
    /** @var MigrationRepository */
    private $migrationRepository;

    /** @var VersionAliasResolver */
    private $versionAliasResolver;

    /**
     * @dataProvider getAliases
     */
    public function testResolveVersionAlias(
        string $alias,
        ?string $expectedVersion,
        ?string $expectedMethod,
        ?string $expectedArgument
    ) : void {
        $this->migrationRepository->expects($this->once())
            ->method('hasVersion')
            ->with($alias)
            ->willReturn(false);

        if ($expectedMethod !== null) {
            $expectation = $this->migrationRepository->expects($this->once())
                ->method($expectedMethod)
                ->willReturn($expectedVersion);

            if ($expectedArgument) {
                $expectation->with($expectedArgument);
            }
        }

        $this->assertEquals($expectedVersion, $this->versionAliasResolver->resolveVersionAlias($alias));
    }

    /**
     * @return mixed[][]
     */
    public function getAliases() : array
    {
        return [
            ['first', '0', null, null],
            ['current', '5', 'getCurrentVersion', null],
            ['prev', '4', 'getPrevVersion', null],
            ['next', '6', 'getNextVersion', null],
            ['latest', '7', 'getLatestVersion', null],
            ['current-5', '2', 'getDeltaVersion', -5],
            ['test-5', null, null, null],
        ];
    }

    public function testResolveVersionAliasHasVersion() : void
    {
        $this->migrationRepository->expects($this->once())
            ->method('hasVersion')
            ->with('test')
            ->willReturn(true);

        $this->assertEquals('test', $this->versionAliasResolver->resolveVersionAlias('test'));
    }

    protected function setUp() : void
    {
        $this->migrationRepository = $this->createMock(MigrationRepository::class);

        $this->versionAliasResolver = new VersionAliasResolver($this->migrationRepository);
    }
}
