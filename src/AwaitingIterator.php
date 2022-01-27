<?php

declare(strict_types=1);

namespace WyriHaximus\React;

use Iterator;
use React\Promise\Deferred;
use SplQueue;

use function React\Async\await;

final class AwaitingIterator implements Iterator
{
    private SplQueue $queue;
    private ?Deferred $next = null;
    private bool $completed = false;
    private int $key        = 0;

    public function __construct()
    {
        $this->queue = new SplQueue();
    }

    public function push(mixed $value): void
    {
        if ($this->next instanceof Deferred) {
            $next       = $this->next;
            $this->next = null;
            $next->resolve($value);

            return;
        }

        $this->queue->enqueue($value);
    }

    public function complete(): void
    {
        $this->completed = true;
    }

    /**
     * @phpstan-ignore-next-line
     */
    public function current(): mixed
    {
        if ($this->queue->count() > 0) {
            return $this->queue->dequeue();
        }

        $this->next = new Deferred();

        return await($this->next->promise());
    }

    public function next(): void
    {
        // no-op
    }

    /**
     * @phpstan-ignore-next-line
     */
    public function key(): mixed
    {
        return $this->key++;
    }

    public function valid(): bool
    {
        return $this->queue->count() > 0 || ! $this->completed;
    }

    public function rewind(): void
    {
        // no-op
    }
}
