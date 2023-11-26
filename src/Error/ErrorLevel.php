<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use ValueError;

use function array_key_exists;
use function array_search;
use function error_reporting;

use const ARRAY_FILTER_USE_KEY;
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
        E_ALL => 'E_ALL',
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_STRICT => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    private function __construct(protected readonly int $value)
    {
        if ($this->value < -1 || $this->value > E_ALL) {
            throw new ValueError('The value `'.$this->value.'` is invalid as an error reporting level in PHP.');
        }
    }

    public static function fromValue(int $value): self
    {
        return new self($value);
    }

    public static function fromName(string $name): self
    {
        /** @var int|false $errorLevel */
        $errorLevel = array_search($name, self::LEVELS, true);
        if (false === $errorLevel) {
            throw new ValueError('The name `'.$name.'` is invalid or an unknown error reporting level name.');
        }

        return new self($errorLevel);
    }

    public static function fromEnvironment(): self
    {
        return new self(error_reporting());
    }

    public static function fromExclusion(self|string|int ...$levels): self
    {
        return new self(array_reduce($levels, function (int $carry, self|string|int $level) {
            $level = match (true) {
                $level instanceof ErrorLevel => $level->value,
                is_string($level) => ErrorLevel::fromName($level)->value,
                default => ErrorLevel::fromValue($level)->value,
            };

            if (!array_key_exists($level, self::LEVELS)) {
                throw new ValueError('The value `'.$level.'` is invalid as a error reporting level value in PHP.');
            }

            return $carry & ~$level;
        }, E_ALL));
    }

    public static function fromInclusion(self|string|int ...$levels): self
    {
        return new self(array_reduce($levels, function (int $carry, self|string|int $level) {
            $level = match (true) {
                $level instanceof ErrorLevel => $level->value,
                is_string($level) => ErrorLevel::fromName($level)->value,
                default => ErrorLevel::fromValue($level)->value,
            };

            if (!array_key_exists($level, self::LEVELS)) {
                throw new ValueError('The value `'.$level.'` is invalid as a error reporting level value in PHP.');
            }

            return $carry | $level;
        }, 0));
    }

    public function value(): int
    {
        return $this->value;
    }

    public function contains(self|string|int ...$levels): bool
    {
        if ([] === $levels) {
            return false;
        }

        $levels = array_map(fn (self|string|int $level): int => match (true) {
            $level instanceof ErrorLevel => $level->value,
            is_string($level) => ErrorLevel::fromName($level)->value,
            is_int($level) => ErrorLevel::fromValue($level)->value,
        }, $levels);

        if (-1 === $this->value) {
            return true;
        }

        if (0 === $this->value) {
            return false;
        }

        foreach ($levels as $level) {
            if (1 > $level || $level !== ($this->value & $level)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string>
     */
    public function included(): array
    {
        return array_values(array_filter(
            self::LEVELS,
            fn (int $error): bool => 0 !== ($error & $this->value),
            ARRAY_FILTER_USE_KEY
        ));
    }

    /**
     * @return array<string>
     */
    public function excluded(): array
    {
        return array_values(array_filter(
            self::LEVELS,
            fn (int $error): bool => 0 === ($error & $this->value),
            ARRAY_FILTER_USE_KEY
        ));
    }
}
