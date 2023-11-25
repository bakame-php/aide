<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use OutOfBoundsException;

use ValueError;

use function array_filter;
use function array_map;
use function error_reporting;
use function in_array;

use const E_ALL;
use const E_COMPILE_ERROR;
use const E_COMPILE_WARNING;
use const E_CORE_WARNING;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_RECOVERABLE_ERROR;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

class ErrorLevel
{
    protected const LEVELS = [
        -1,
        0,
        E_ERROR,
        E_WARNING,
        E_PARSE,
        E_NOTICE,
        E_CORE_ERROR,
        E_CORE_WARNING,
        E_COMPILE_ERROR,
        E_COMPILE_WARNING,
        E_RECOVERABLE_ERROR,
        E_ALL,
        E_DEPRECATED,
        E_STRICT,
        E_USER_ERROR,
        E_USER_WARNING,
        E_USER_NOTICE,
        E_USER_DEPRECATED,
    ];

    private function __construct(protected readonly int $value)
    {
        if ($this->value < -1) {
            throw new ValueError('The value `'.$this->value.'` is invalid as a error reporting level in PHP.');
        }
    }

    public static function new(int $bytes = 0): self
    {
        return new self($bytes);
    }

    public static function fromEnvironment(): self
    {
        $errorReporting = error_reporting(-1);
        error_reporting($errorReporting);

        return new self($errorReporting);
    }

    public function toBytes(): int
    {
        return $this->value;
    }

    public function contains(self|int ...$levels): bool
    {
        foreach ($levels as $level) {
            $level = $level instanceof self ? $level->toBytes() : $level;
            if (0 !== ($level & $this->toBytes())) {
                return true;
            }
        }

        return false;
    }

    public function include(self|int ...$levels): self
    {
        $levels = array_map(fn (self|int $level) => match (true) {
            $level instanceof self => $level->value,
            default => $level,
        }, $levels);

        if ([] === $levels) {
            return $this;
        }

        if (in_array(-1, $levels, true)) {
            return self::new(-1);
        }

        if (in_array(0, $levels, true)) {
            return self::new(0);
        }

        $value = 0 === $this->value ? $levels[0] : $this->value;
        foreach ($levels as $level) {
            if (!in_array($level, self::LEVELS, true)) {
                throw new OutOfBoundsException('The error reporting level value `'.$level.'` is invalid.');
            }

            $value &= $level;
        }

        return self::new($value);
    }

    public function ignore(self|int ...$levels): self
    {
        $levels = array_map(fn (self|int $level) => match (true) {
            $level instanceof self => $level->value,
            default => $level,
        }, $levels);

        if (in_array(-1, $levels, true)) {
            return self::new(0);
        }

        $levels = array_filter($levels, fn (int $level) => 0 !== $level && $this->value !== $level);
        if ([] === $levels) {
            return $this;
        }

        $value = $this->value;
        foreach ($levels as $level) {
            if (!in_array($level, self::LEVELS, true)) {
                throw new OutOfBoundsException('The error reporting level value `'.$level.'` is invalid.');
            }
            $value &= ~$level;
        }

        return self::new($value);
    }
}
