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
    protected CloakedErrors $errors;
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
        $this->errors = new CloakedErrors();
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
     * @throws CloakedErrors
     */
    public function __invoke(mixed ...$arguments): mixed
    {
        if ($this->errors->isNotEmpty()) {
            $this->errors = new CloakedErrors();
        }

        $errorHandler = function (int $errno, string $errstr, string $errfile, int $errline): bool {
            if (0 === (error_reporting() & $errno)) {
                return false;
            }

            $this->errors->unshift(new ErrorException($errstr, 0, $errno, $errfile, $errline));

            return true;
        };

        try {
            set_error_handler($errorHandler, $this->errorLevel->toBytes());
            $result = ($this->closure)(...$arguments);
        } finally {
            restore_error_handler();
        }

        if ($this->errors->isEmpty()) {
            return $result;
        }

        if (self::THROW === $this->onError) {
            throw $this->errors;
        }

        if (self::SILENT === $this->onError) {
            return $result;
        }

        if (true === self::$useException) {
            throw $this->errors;
        }

        return $result;
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
            || (self::SILENT !== $this->onError && true === self::$useException);
    }
}
