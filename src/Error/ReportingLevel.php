<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use ValueError;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_reduce;
use function array_search;
use function array_values;
use function error_reporting;
use function is_string;

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

/**
 * @method static ReportingLevel all()
 * @method static ReportingLevel error()
 * @method static ReportingLevel warning()
 * @method static ReportingLevel parse()
 * @method static ReportingLevel notice()
 * @method static ReportingLevel coreError()
 * @method static ReportingLevel coreWarning()
 * @method static ReportingLevel compileError()
 * @method static ReportingLevel compileWarning()
 * @method static ReportingLevel userError()
 * @method static ReportingLevel userWarning()
 * @method static ReportingLevel userNotice()
 * @method static ReportingLevel strict()
 * @method static ReportingLevel recoverableError()
 * @method static ReportingLevel deprecated()
 * @method static ReportingLevel userDeprecated()
 */
class ReportingLevel
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

    protected function __construct(protected readonly int $value)
    {
        if ($this->value < -1 || $this->value > E_ALL) {
            throw new ValueError('The value `'.$this->value.'` is invalid as an error reporting level in PHP.');
        }
    }

    /**
     * Returns a new instance by using the error reporting level value.
     */
    public static function fromValue(int $value): self
    {
        return new self($value);
    }

    /**
     * Returns a new instance by using the error reporting level constant name.
     */
    public static function fromName(string $name): self
    {
        /** @var int|false $errorLevel */
        $errorLevel = array_search($name, self::LEVELS, true);
        if (false === $errorLevel) {
            throw new ValueError('The name `'.$name.'` is invalid or an unknown error reporting level name.');
        }

        return new self($errorLevel);
    }

    public static function fromEnv(): self
    {
        return new self(error_reporting());
    }

    /**
     * Returns a new instance by excluded error levels from E_ALL.
     */
    public static function fromExclusion(self|string|int ...$levels): self
    {
        return new self(array_reduce($levels, function (int $carry, self|string|int $level) {
            $level = match (true) {
                $level instanceof ReportingLevel => $level->value,
                is_string($level) => ReportingLevel::fromName($level)->value,
                default => ReportingLevel::fromValue($level)->value,
            };

            if (!array_key_exists($level, self::LEVELS)) {
                throw new ValueError('The value `'.$level.'` is invalid as a error reporting level value in PHP.');
            }

            return $carry & ~$level;
        }, E_ALL));
    }

    /**
     * Returns a new instance by adding error levels from the initial no error reporting level.
     */
    public static function fromInclusion(self|string|int ...$levels): self
    {
        return new self(array_reduce($levels, function (int $carry, self|string|int $level) {
            $level = match (true) {
                $level instanceof ReportingLevel => $level->value,
                is_string($level) => ReportingLevel::fromName($level)->value,
                default => ReportingLevel::fromValue($level)->value,
            };

            if (!array_key_exists($level, self::LEVELS)) {
                throw new ValueError('The value `'.$level.'` is invalid as a error reporting level value in PHP.');
            }

            return $carry | $level;
        }, 0));
    }

    /**
     * @param array<mixed> $arguments
     */
    public static function __callStatic(string $name, array $arguments = []): self
    {
        return self::fromName(
            'E_'.strtoupper(
                (string) preg_replace('/(.)(?=[A-Z])/u', '$1_', $name)
            )
        );
    }

    public function value(): int
    {
        return $this->value;
    }

    public function doesNotContain(self|string|int ...$levels): bool
    {
        return !$this->contains(...$levels);
    }

    public function contains(self|string|int ...$levels): bool
    {
        if ([] === $levels) {
            return false;
        }

        $levels = array_map(fn (self|string|int $level): int => match (true) {
            $level instanceof ReportingLevel => $level->value,
            is_string($level) => ReportingLevel::fromName($level)->value,
            default => ReportingLevel::fromValue($level)->value,
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
