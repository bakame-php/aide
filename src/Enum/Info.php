<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use ReflectionEnum;

use function array_column;
use function array_search;
use function count;

trait Info
{
    public static function isBacked(): bool
    {
        return (new ReflectionEnum(static::class))->isBacked(); /* @phpstan-ignore-line */
    }

    public static function isPure(): bool
    {
        return !static::isBacked();
    }

    public static function size(): int
    {
        return count(static::cases());
    }

    /**
     * List the Enum backed name.
     *
     * @return list<string>
     */
    public static function names(): array
    {
        return array_column(static::cases(), 'name');
    }

    /**
     * List the Enum values if they exist.
     * Returns an empty array for non-backed Enumeration.
     *
     * @return list<string|int>
     */
    public static function values(): array
    {
        return array_column(static::cases(), 'value');
    }

    /**
     * Returns the enumeration name associated to the value if it exists.
     * Otherwise, null is returned.
     */
    public static function nameOf(string|int $value): ?string
    {
        /** @var string|false $res */
        $res = array_search(
            $value,
            array_column(static::cases(), 'value', 'name'),
            true
        );

        return match ($res) {
            false => null,
            default => $res,
        };
    }
}
