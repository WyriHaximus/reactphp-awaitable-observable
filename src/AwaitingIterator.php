<?php

declare(strict_types=1);

namespace WyriHaximus\React;

use Iterator;
use React\Promise\Deferred;
use Rx\DisposableInterface;
use Rx\ObservableInterface;
use SplQueue;
use Throwable;

use function React\Async\await;

/**
 * @template T
 * @template-implements Iterator<T>
 */
final class AwaitingIterator implements Iterator
{
    /** @var SplQueue<T> */
    private readonly SplQueue $queue;
    private DisposableInterface|null $disposable = null;
    private ObservableInterface|null $observable;
    /** @var Deferred<bool>|null */
    private Deferred|null $valid = null;
    private bool $completed      = false;
    private int $key             = 0;

    /** @param ObservableInterface<T> $observable */
    public function __construct(ObservableInterface $observable)
    {
        $this->queue      = new SplQueue();
        $this->observable = $observable;
    }

    /** @api */
    public function break(): void
    {
        $this->disposable?->dispose();
        $this->completed = true;
    }

    /** @param T $value */
    private function push(mixed $value): void
    {
        $this->queue->enqueue($value);
        if (! $this->valid instanceof Deferred) {
            return;
        }

        $valid       = $this->valid;
        $this->valid = null;
        $valid->resolve(true);
    }

    private function complete(): void
    {
        $this->completed = true;
        if (! $this->valid instanceof Deferred) {
            return;
        }

        $valid       = $this->valid;
        $this->valid = null;
        $valid->resolve(false);
    }

    // phpcs:disable
    /**
     * @return T
     */
    public function current(): mixed
    {
        return $this->queue->dequeue();
    }
    // phpcs:enable

    public function next(): void
    {
        // no-op
    }

    // phpcs:disable
    /**
     * @return mixed
     */
    public function key(): mixed
    {
        return $this->key++;
    }
    // phpcs:enable

    public function valid(): bool
    {
        if (! $this->disposable instanceof DisposableInterface) {
            $observable       = $this->observable;
            $this->observable = null;
            $this->disposable = $observable?->subscribe(
                function (mixed $value): void {
                    $this->push($value);
                },
                static function (Throwable $throwable): never {
                    throw $throwable;
                },
                function (): void {
                    $this->complete();
                },
            );
        }

        if ($this->queue->count() > 0) {
            return true;
        }

        if (! $this->completed) {
            /** @var Deferred<bool> $deferred */
            $deferred    = new Deferred();
            $this->valid = $deferred;
            unset($deferred);

            /** @phpstan-ignore return.type */
            return await($this->valid->promise());
        }

        return false;
    }

    public function rewind(): void
    {
        // no-op
    }
}
