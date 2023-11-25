<?php

declare(strict_types=1);

namespace Bakame\Aide\Error;

use Countable;
use ErrorException;
use Iterator;
use IteratorAggregate;
use RuntimeException;

use function array_unshift;
use function count;

/**
 * @implements IteratorAggregate<int, ErrorException>
 */
final class CloakedErrors extends RuntimeException implements Countable, IteratorAggregate
{
    /** @var array<ErrorException> */
    private array $errorExceptions;

    public function __construct(string $message = '')
    {
        parent::__construct($message);
        $this->errorExceptions = [];
    }

    public function count(): int
    {
        return count($this->errorExceptions);
    }

    /**
     * @return Iterator<int, ErrorException>
     */
    public function getIterator(): Iterator
    {
        yield from $this->errorExceptions;
    }

    public function unshift(ErrorException $exception): void
    {
        array_unshift($this->errorExceptions, $exception);
    }

    public function isEmpty(): bool
    {
        return [] === $this->errorExceptions;
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
            $offset += count($this->errorExceptions);
        }

        return $this->errorExceptions[$offset] ?? null;
    }
}
