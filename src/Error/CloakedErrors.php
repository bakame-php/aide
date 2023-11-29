<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use Countable;
use ErrorException;
use Iterator;
use IteratorAggregate;

use function count;

/**
 * @implements IteratorAggregate<int, ErrorException>
 */
class CloakedErrors implements Countable, IteratorAggregate
{
    /** @var array<ErrorException>  */
    private array $errors;

    public function __construct(ErrorException ...$errors)
    {
        $this->errors = $errors;
    }

    public function count(): int
    {
        return count($this->errors);
    }

    /**
     * @return Iterator<int, ErrorException>
     */
    public function getIterator(): Iterator
    {
        yield from $this->errors;
    }

    public function unshift(ErrorException $exception): void
    {
        array_unshift($this->errors, $exception);
    }

    public function reset(): void
    {
        $this->errors = [];
    }

    public function isEmpty(): bool
    {
        return [] === $this->errors;
    }

    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    public function first(): ?ErrorException
    {
        return $this->get(-1);
    }

    public function last(): ?ErrorException
    {
        return $this->get(0);
    }

    public function get(int $offset): ?ErrorException
    {
        if ($offset < 0) {
            $offset += count($this->errors);
        }

        return $this->errors[$offset] ?? null;
    }
}
