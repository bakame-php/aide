<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use Closure;
use ErrorException;

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
    public const FORCE_NOTHING = 0;
    public const SILENCE_ERROR = 1;
    public const THROW_ON_ERROR = 2;

    protected static bool $useException = false;
    protected ?ErrorException $exception = null;

    public function __construct(
        protected readonly Closure $closure,
        protected readonly int $errorLevel = E_WARNING,
        protected readonly int $behaviour = self::FORCE_NOTHING
    ) {
    }

    public static function throwOnError(): void
    {
        self::$useException = true;
    }

    public static function silenceError(): void
    {
        self::$useException = false;
    }

    public static function warning(Closure $closure, int $behaviour = self::FORCE_NOTHING): self
    {
        return new self($closure, E_WARNING, $behaviour);
    }

    public static function notice(Closure $closure, int $behaviour = self::FORCE_NOTHING): self
    {
        return new self($closure, E_NOTICE, $behaviour);
    }

    public static function deprecated(Closure $closure, int $behaviour = self::FORCE_NOTHING): self
    {
        return new self($closure, E_DEPRECATED, $behaviour);
    }

    public static function strict(Closure $closure, int $behaviour = self::FORCE_NOTHING): self
    {
        return new self($closure, E_STRICT, $behaviour);
    }

    public static function userWarning(Closure $closure, int $behaviour = self::FORCE_NOTHING): self
    {
        return new self($closure, E_USER_WARNING, $behaviour);
    }

    public static function userNotice(Closure $closure, int $behaviour = self::FORCE_NOTHING): self
    {
        return new self($closure, E_USER_NOTICE, $behaviour);
    }

    public static function userDeprecated(Closure $closure, int $behaviour = self::FORCE_NOTHING): self
    {
        return new self($closure, E_USER_DEPRECATED, $behaviour);
    }

    public static function all(Closure $closure, int $behaviour = self::FORCE_NOTHING): self
    {
        return new self($closure, E_ALL, $behaviour);
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

        set_error_handler($errorHandler, $this->errorLevel);
        $result = ($this->closure)(...$arguments);
        restore_error_handler();

        if (null === $this->exception) { /* @phpstan-ignore-line */
            return $result;
        }

        if (self::THROW_ON_ERROR === $this->behaviour) { /* @phpstan-ignore-line */
            throw $this->exception;
        }

        if (self::SILENCE_ERROR === $this->behaviour) {
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
        return self::THROW_ON_ERROR === $this->behaviour
            || (self::SILENCE_ERROR !== $this->behaviour && true === self::$useException);
    }

    public function suppressAll(): bool
    {
        return $this->suppress(E_ALL);
    }

    public function suppressWarning(): bool
    {
        return $this->suppress(E_WARNING);
    }

    public function suppressNotice(): bool
    {
        return $this->suppress(E_NOTICE);
    }

    public function suppressDeprecated(): bool
    {
        return $this->suppress(E_DEPRECATED);
    }

    public function suppressStrict(): bool
    {
        return $this->suppress(E_STRICT);
    }

    public function suppressUserWarning(): bool
    {
        return $this->suppress(E_USER_WARNING);
    }

    public function suppressUserNotice(): bool
    {
        return $this->suppress(E_USER_NOTICE);
    }

    public function suppressUserDeprecated(): bool
    {
        return $this->suppress(E_USER_DEPRECATED);
    }

    public function suppress(int $errorLevel): bool
    {
        return 0 !== ($errorLevel & $this->errorLevel);
    }
}
