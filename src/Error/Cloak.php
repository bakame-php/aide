<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use ArgumentCountError;
use Closure;
use ErrorException;
use ValueError;

use function in_array;
use function is_int;
use function is_string;
use function restore_error_handler;
use function set_error_handler;

/**
 * @method static Cloak all(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak error(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak warning(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak parse(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak notice(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak coreError(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak coreWarning(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak compileError(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak compileWarning(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak userError(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak userWarning(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak userNotice(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak strict(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak recoverableError(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak deprecated(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 * @method static Cloak userDeprecated(Closure $closure, ?int $onError = self::FOLLOW_ENV)
 */
class Cloak
{
    public const FOLLOW_ENV = 0;
    public const SILENT = 1;
    public const THROW = 2;

    protected static bool $useException = false;

    protected readonly ErrorLevel $errorLevel;
    protected CloakedErrors $errors;

    /**
     * @throws ValueError
     */
    public function __construct(
        protected readonly Closure $closure,
        protected readonly int $onError = self::FOLLOW_ENV,
        ErrorLevel|string|int|null $errorLevel = null
    ) {
        if (!in_array($this->onError, [self::SILENT, self::THROW, self::FOLLOW_ENV], true)) {
            throw new ValueError('The `onError` value is invalid; expect one of the `'.self::class.'` constants.');
        }

        $this->errorLevel = match (true) {
            $errorLevel instanceof ErrorLevel => $errorLevel,
            is_string($errorLevel) => ErrorLevel::fromName($errorLevel),
            is_int($errorLevel) => ErrorLevel::fromValue($errorLevel),
            default => ErrorLevel::fromEnvironment(),
        };

        $this->errors = new CloakedErrors();
    }

    public function errors(): CloakedErrors
    {
        return $this->errors;
    }

    public function errorLevel(): ErrorLevel
    {
        return $this->errorLevel;
    }

    public function errorsAreSilenced(): bool
    {
        return !$this->errorsAreThrown();
    }

    public function errorsAreThrown(): bool
    {
        return self::THROW === $this->onError
            || (self::FOLLOW_ENV === $this->onError && true === self::$useException);
    }

    /**
     * @throws CloakedErrors
     */
    public function __invoke(mixed ...$arguments): mixed
    {
        $this->errors = new CloakedErrors();
        try {
            set_error_handler($this->errorHandler(...), $this->errorLevel->value());
            $result = ($this->closure)(...$arguments);
        } finally {
            restore_error_handler();
        }

        return $result;
    }

    protected function errorHandler(int $errno, string $errstr, string $errfile = null, int $errline = null): bool
    {
        if (ErrorLevel::fromEnvironment()->doesNotContain($errno)) {
            return false;
        }

        $this->errors->unshift(new ErrorException($errstr, 0, $errno, $errfile, $errline));

        return match ($this->errorsAreThrown()) {
            true => throw $this->errors(),
            false => true,
        };
    }

    public static function throwOnError(): void
    {
        self::$useException = true;
    }

    public static function silentOnError(): void
    {
        self::$useException = false;
    }

    public static function fromEnvironment(Closure $closure, int $onError = self::FOLLOW_ENV): self
    {
        return new self($closure, $onError);
    }

    /**
     * @param array{0:Closure, 1:self::FOLLOW_ENV|self::SILENT|self::THROW|null} $arguments
     */
    public static function __callStatic(string $name, array $arguments): self
    {
        return match (true) {
            1 > count($arguments) => throw new ArgumentCountError('The method expects at least 2 arguments; '.count($arguments).' was given.'),
            default => new self($arguments[0], $arguments[1] ?? self::FOLLOW_ENV, ErrorLevel::__callStatic($name)),
        };
    }
}
