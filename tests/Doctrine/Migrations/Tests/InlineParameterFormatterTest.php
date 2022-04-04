<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\InlineParameterFormatter;
use PHPUnit\Framework\TestCase;

class InlineParameterFormatterTest extends TestCase
{
    private Connection $connection;

    private AbstractPlatform $platform;

    private InlineParameterFormatter $parameterFormatter;

    public function testFormatParameters(): void
    {
        $params = [
            0       => 'string value',
            1       => 1,
            2       => [1, true, false, 'string value'],
            3       => true,
            4       => false,
            5       => 'string value',
            6       => 1,
            7       => true,
            8       => false,
            9       => [1, true, false, 'string value'],
            'named' => 'string value',
        ];

        $types = [
            Types::STRING,
            Types::INTEGER,
            Types::SIMPLE_ARRAY,
            Types::BOOLEAN,
            Types::BOOLEAN,
            'unknown',
            'unknown',
            'unknown',
            'unknown',
            'unknown',
            'unknown',
        ];

        $result = $this->parameterFormatter->formatParameters($params, $types);

        $expected = 'with parameters ([string value], [1], [1,1,,string value], [], [], [string value], [1], [true], [false], [1, true, false, string value], :named => [string value])';

        self::assertSame($expected, $result);
    }

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);

        $this->platform = $this->createMock(AbstractPlatform::class);

        $this->connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn($this->platform);

        $this->parameterFormatter = new InlineParameterFormatter($this->connection);
    }
}
