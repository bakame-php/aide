<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use Closure;
use ErrorException;

use function error_reporting;
use function restore_error_handler;
use function set_error_handler;

use const E_ALL;
use const E_DEPRECATED;
use const E_NOTICE;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;

class Cloak
{
    public const FOLLOW_ENV = 0;
    public const SILENT = 1;
    public const THROW = 2;

    protected static bool $useException = false;
    protected ?ErrorException $exception = null;
    protected readonly ErrorLevel $errorLevel;

    public function __construct(
        protected readonly Closure $closure,
        protected readonly int $onError = self::FOLLOW_ENV,
        ErrorLevel|int|null $errorLevel = null
    ) {
        $errorLevel = $errorLevel ?? ErrorLevel::fromEnvironment();
        if (!$errorLevel instanceof ErrorLevel) {
            $errorLevel = ErrorLevel::new($errorLevel);
        }

        $this->errorLevel = $errorLevel;
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
        return new self(closure: $closure, onError: $onError, errorLevel: $onError);
    }

    public static function warning(Closure $closure, int $onError = self::FOLLOW_ENV): self
    {
        return new self($closure, $onError, E_WARNING);
    }

    public static function notice(Closure $closure, int $onError = self::FOLLOW_ENV): self
    {
        return new self($closure, $onError, E_NOTICE);
    }

    public static function deprecated(Closure $closure, int $onError = self::FOLLOW_ENV): self
    {
        return new self($closure, $onError, E_DEPRECATED);
    }

    public static function strict(Closure $closure, int $onError = self::FOLLOW_ENV): self
    {
        return new self($closure, $onError, E_STRICT);
    }

    public static function userWarning(Closure $closure, int $onError = self::FOLLOW_ENV): self
    {
        return new self($closure, $onError, E_USER_WARNING);
    }

    public static function userNotice(Closure $closure, int $onError = self::FOLLOW_ENV): self
    {
        return new self($closure, $onError, E_USER_NOTICE);
    }

    public static function userDeprecated(Closure $closure, int $onError = self::FOLLOW_ENV): self
    {
        return new self($closure, $onError, E_USER_DEPRECATED);
    }

    public static function all(Closure $closure, int $onError = self::FOLLOW_ENV): self
    {
        return new self($closure, $onError, E_ALL);
    }

    /**
     * @throws ErrorException
     */
    public function __invoke(mixed ...$arguments): mixed
    {
        $this->exception = null;
        $errorHandler = function (int $errno, string $errstr, string $errfile, int $errline): bool {
            if (0 === (error_reporting() & $errno)) {
                return false;
            }

            $this->exception = new ErrorException($errstr, 0, $errno, $errfile, $errline);

            return true;
        };

        set_error_handler($errorHandler, $this->errorLevel->toBytes());
        $result = ($this->closure)(...$arguments);
        restore_error_handler();

        if (null === $this->exception) { /* @phpstan-ignore-line */
            return $result;
        }

        if (self::THROW === $this->onError) { /* @phpstan-ignore-line */
            throw $this->exception;
        }

        if (self::SILENT === $this->onError) {
            return $result;
        }

        if (true === self::$useException) {
            throw $this->exception;
        }

        return $result;
    }

    public function lastError(): ?ErrorException
    {
        return $this->exception;
    }

    public function errorsAreSilenced(): bool
    {
        return !$this->errorsAreThrown();
    }

    public function errorsAreThrown(): bool
    {
        return self::THROW === $this->onError
            || (self::SILENT !== $this->onError && true === self::$useException);
    }

    public function includeAll(): bool
    {
        return $this->include(E_ALL);
    }

    public function includeWarning(): bool
    {
        return $this->include(E_WARNING);
    }

    public function includeNotice(): bool
    {
        return $this->include(E_NOTICE);
    }

    public function includeDeprecated(): bool
    {
        return $this->include(E_DEPRECATED);
    }

    public function includeStrict(): bool
    {
        return $this->include(E_STRICT);
    }

    public function includeUserWarning(): bool
    {
        return $this->include(E_USER_WARNING);
    }

    public function includeUserNotice(): bool
    {
        return $this->include(E_USER_NOTICE);
    }

    public function includeUserDeprecated(): bool
    {
        return $this->include(E_USER_DEPRECATED);
    }

    public function include(ErrorLevel|int $errorLevel): bool
    {
        return $this->errorLevel->contains($errorLevel);
    }
}
