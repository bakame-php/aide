<?php

declare(strict_types=1);

namespace Bakame\Aide\Enum;

use ReflectionEnum;
use UnitEnum;

use function is_int;
use function is_string;

trait Compare
{
    public function equals(mixed $value): bool
    {
        return $value instanceof UnitEnum && $value === $this;
    }

    public function isOneOf(int|string|UnitEnum ...$values): bool
    {
        $type = (new ReflectionEnum($this))->getBackingType()?->getName();

        $isSatisfiedBy = fn (int|string|UnitEnum $value): bool => match (true) {
            $value instanceof UnitEnum && $value === $this,
            ('int' === $type && is_int($value) || 'string' === $type && is_string($value)) && static::tryFrom($value) === $this,
            $value === $this->name => true,
            default => false,
        };

        foreach ($values as $value) {
            if ($isSatisfiedBy($value)) {
                return true;
            }
        }

        return false;
    }

    public function notEquals(mixed $value): bool
    {
        return !$this->equals($value);
    }

    public function isNotOneOf(int|string|UnitEnum ...$value): bool
    {
        return !$this->isOneOf(...$value);
    }
}
