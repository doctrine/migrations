<?php

declare(strict_types=1);

namespace Doctrine\Migrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use function array_map;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function sprintf;

/**
 * The InlineParameterFormatter class is responsible for formatting SQL query parameters to a string
 * for display output.
 *
 * @internal
 */
final class InlineParameterFormatter implements ParameterFormatter
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @param mixed[] $params
     * @param mixed[] $types
     */
    public function formatParameters(array $params, array $types): string
    {
        if ($params === []) {
            return '';
        }

        $formattedParameters = [];

        foreach ($params as $key => $value) {
            $type = $types[$key] ?? 'string';

            $formattedParameter = '[' . $this->formatParameter($value, $type) . ']';

            $formattedParameters[] = is_string($key)
                ? sprintf(':%s => %s', $key, $formattedParameter)
                : $formattedParameter;
        }

        return sprintf('with parameters (%s)', implode(', ', $formattedParameters));
    }

    private function formatParameter(mixed $value, string|int $type): string|int|float|null
    {
        if (is_string($type) && Type::hasType($type)) {
            return Type::getType($type)->convertToDatabaseValue(
                $value,
                $this->connection->getDatabasePlatform(),
            );
        }

        return $this->parameterToString($value);
    }

    /** @param int[]|bool[]|string[]|float[]|array|int|string|float|bool $value */
    private function parameterToString(array|int|string|float|bool $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map($this->parameterToString(...), $value));
        }

        if (is_int($value) || is_string($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value === true ? 'true' : 'false';
        }
    }
}
