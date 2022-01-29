<?php

declare(strict_types=1);

namespace WyriHaximus\React;

use Iterator;
use React\Promise\Deferred;
use Rx\DisposableInterface;
use Rx\Observable;
use SplQueue;
use Throwable;

use function React\Async\await;

final class AwaitingIterator implements Iterator
{
    private SplQueue $queue;
    private DisposableInterface $disposable;
    private ?Deferred $next = null;
    private bool $completed = false;
    private int $key        = 0;

    public function __construct(Observable $observable)
    {
        $this->queue      = new SplQueue();
        $this->disposable = $observable->subscribe(
            function (mixed $value): void {
                    $this->push($value);
            },
            static function (Throwable $throwable): void {
                    throw $throwable;
            },
            function (): void {
                    $this->complete();
            },
        );
    }

    public function break(): void
    {
        $this->disposable->dispose();
        $this->completed = true;
    }

    private function push(mixed $value): void
    {
        if ($this->next instanceof Deferred) {
            $next       = $this->next;
            $this->next = null;
            $next->resolve($value);

            return;
        }

        $this->queue->enqueue($value);
    }

    private function complete(): void
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
        return ! $this->completed || $this->queue->count() > 0;
    }

    public function rewind(): void
    {
        // no-op
    }
}
