<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use ReflectionEnum;

use function is_int;
use function is_string;

trait Hasser
{
    public static function has(int|string $value): bool
    {
        $type = (new ReflectionEnum(static::class))->getBackingType()?->getName();

        return match (true) {
            ('int' === $type && is_int($value) || 'string' === $type && is_string($value)) => null !== static::tryFrom($value),  /* @phpstan-ignore-line */
            is_int($value) => false,
            default => static::hasName($value),
        };
    }

    /**
     * Tells whether the name is used as a case name.
     *
     * Returns true on success, false otherwise.
     */
    public static function hasName(string $name): bool
    {
        foreach (static::cases() as $enum) {
            if ($enum->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tells whether the value is part of the enumeration.
     *
     * Returns true on success, false otherwise.
     */
    public static function hasValue(int|string $value): bool
    {
        foreach (static::cases() as $enum) {
            if ($enum?->value === $value) { /* @phpstan-ignore-line */
                return true;
            }
        }

        return false;
    }

    /**
     * Tells whether the value/name pair is part of the enumeration.
     *
     * Returns true on success, false otherwise.
     */
    public static function hasCase(string $name, int|string $value): bool
    {
        foreach (static::cases() as $enum) {
            if ($enum?->name === $name && $enum?->value === $value) { /* @phpstan-ignore-line */
                return true;
            }
        }

        return false;
    }
}
