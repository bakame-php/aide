<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use ValueError;

trait Factory
{
    public static function tryFrom(string $value): ?static
    {
        return static::tryFromName($value);
    }

    public static function from(string $value): static
    {
        return static::fromName($value);
    }

    public static function tryFromName(string $name): ?static
    {
        foreach (static::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }

    public static function fromName(string $name): static
    {
        $instance = static::tryFromName($name);

        return match (null) {
            $instance => throw new ValueError('"'.$name.'" is not a valid name for "'.static::class.'" enumeration.'),
            default => $instance,
        };
    }
}
