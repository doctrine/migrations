<?php

declare(strict_types=1);

namespace Doctrine\Migrations\Tests;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\InlineParameterFormatter;
use PHPUnit\Framework\TestCase;

class InlineParameterFormatterTest extends TestCase
{
    private InlineParameterFormatter $parameterFormatter;

    public function testFormatParameters(): void
    {
        $params = [
            0       => 'string value',
            1       => true,
            2       => false,
            3       => 1,
            4       => 1.5,
            5       => [1, true, false, 'string value'],
            6       => true,
            7       => false,
            8       => 'string value',
            9       => 1,
            10      => 1.5,
            11      => true,
            12      => false,
            13      => [1, true, false, 'string value'],
            14      => 'string value',
            15      => [1, 2, 3],
            'named' => 'string value',
        ];

        $types = [
            Types::STRING,
            Types::STRING,
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
            ParameterType::STRING,
            ArrayParameterType::INTEGER,
        ];

        $result = $this->parameterFormatter->formatParameters($params, $types);

        $expected = 'with parameters ([string value], [1], [], [1], [1.5], [1,1,,string value], [], [], [string value], [1], [1.5], [true], [false], [1, true, false, string value], [string value], [1, 2, 3], :named => [string value])';

        self::assertSame($expected, $result);
    }

    protected function setUp(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn(self::createStub(AbstractPlatform::class));

        $this->parameterFormatter = new InlineParameterFormatter($connection);
    }
}
