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
            2       => 1.5,
            3       => [1, true, false, 'string value'],
            4       => true,
            5       => false,
            6       => 'string value',
            7       => 1,
            8       => 1.5,
            9       => true,
            10       => false,
            11      => [1, true, false, 'string value'],
            'named' => 'string value',
        ];

        $types = [
            Types::STRING,
            Types::INTEGER,
            Types::FLOAT,
            Types::SIMPLE_ARRAY,
            Types::BOOLEAN,
            Types::BOOLEAN,
            'unknown',
            'unknown',
            'unknown',
            'unknown',
            'unknown',
            'unknown',
            'unknown',
        ];

        $result = $this->parameterFormatter->formatParameters($params, $types);

        $expected = 'with parameters ([string value], [1], [1.5], [1,1,,string value], [], [], [string value], [1], [1.5], [true], [false], [1, true, false, string value], :named => [string value])';

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
