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
 * @method static Cloak all(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak error(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak warning(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak parse(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak notice(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak coreError(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak coreWarning(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak compileError(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak compileWarning(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak userError(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak userWarning(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak userNotice(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak strict(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak recoverableError(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak deprecated(Closure $callback, ?int $onError = self::OBEY)
 * @method static Cloak userDeprecated(Closure $callback, ?int $onError = self::OBEY)
 */
class Cloak
{
    public const OBEY = 0;
    public const SILENT = 1;
    public const THROW = 2;

    protected static bool $useException = false;

    protected readonly ReportingLevel $reportingLevel;
    protected readonly CloakedErrors $errors;

    /**
     * @throws ValueError
     */
    public function __construct(
        protected readonly Closure $callback,
        protected readonly int $onError = self::OBEY,
        ReportingLevel|string|int|null $reportingLevel = null
    ) {
        if (!in_array($this->onError, [self::SILENT, self::THROW, self::OBEY], true)) {
            throw new ValueError('The `onError` value is invalid; expect one of the `'.self::class.'` constants.');
        }

        $this->reportingLevel = match (true) {
            $reportingLevel instanceof ReportingLevel => $reportingLevel,
            is_string($reportingLevel) => ReportingLevel::fromName($reportingLevel),
            is_int($reportingLevel) => ReportingLevel::fromValue($reportingLevel),
            default => ReportingLevel::fromEnv(),
        };

        $this->errors = new CloakedErrors();
    }

    public function errors(): CloakedErrors
    {
        return clone $this->errors;
    }

    public function reportingLevel(): ReportingLevel
    {
        return $this->reportingLevel;
    }

    public function errorsAreSilenced(): bool
    {
        return !$this->errorsAreThrown();
    }

    public function errorsAreThrown(): bool
    {
        return self::THROW === $this->onError
            || (self::OBEY === $this->onError && true === self::$useException);
    }

    public function __invoke(mixed ...$arguments): mixed
    {
        $this->errors->reset();
        try {
            set_error_handler($this->errorHandler(...), $this->reportingLevel->value());
            $result = ($this->callback)(...$arguments);
        } finally {
            restore_error_handler();
        }

        return $result;
    }

    /**
     * @throws ErrorException
     */
    protected function errorHandler(int $errno, string $errstr, ?string $errfile = null, ?int $errline = null): bool
    {
        if (ReportingLevel::fromEnv()->doesNotContain($errno)) {
            return false;
        }

        $this->errors->unshift(new ErrorException($errstr, 0, $errno, $errfile, $errline));
        if ($this->errorsAreThrown()) {
            /** @var ErrorException $error */
            $error = $this->errors->last();

            throw $error;
        }

        return true;
    }

    public static function throwOnError(): void
    {
        self::$useException = true;
    }

    public static function silentOnError(): void
    {
        self::$useException = false;
    }

    public static function env(Closure $callback, int $onError = self::OBEY): self
    {
        return new self($callback, $onError, ReportingLevel::fromEnv());
    }

    /**
     * @param array{0:Closure, 1:self::OBEY|self::SILENT|self::THROW|null} $arguments
     */
    public static function __callStatic(string $name, array $arguments): self
    {
        return match (true) {
            1 > count($arguments) => throw new ArgumentCountError('The method expects at least 1 argument; '.count($arguments).' was given.'),
            default => new self(
                $arguments[0],
                $arguments[1] ?? self::OBEY,
                ReportingLevel::__callStatic($name),
            ),
        };
    }
}
