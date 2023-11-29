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

/** @psalm-suppress MissingTemplateParam */
final class AwaitingIterator implements Iterator
{
    private SplQueue $queue;
    private DisposableInterface $disposable;
    private Deferred|null $valid = null;
    private bool $completed      = false;
    private int $key             = 0;

    public function __construct(ObservableInterface $observable)
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
        $this->queue->enqueue($value);
        if ($this->valid === null) {
            return;
        }

        $valid       = $this->valid;
        $this->valid = null;
        $valid->resolve(true);
    }

    private function complete(): void
    {
        $this->completed = true;
        if ($this->valid === null) {
            return;
        }

        $valid       = $this->valid;
        $this->valid = null;
        $valid->resolve(false);
    }

    // phpcs:disable
    /**
     * @return mixed
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

    /** @psalm-suppress MixedInferredReturnType */
    public function valid(): bool
    {
        if ($this->queue->count() > 0) {
            return true;
        }

        if (! $this->completed) {
            $this->valid = new Deferred();

            /**
             * @phpstan-ignore-next-line
             * @psalm-suppress MixedReturnStatement
             */
            return await($this->valid->promise());
        }

        return false;
    }

    public function rewind(): void
    {
        // no-op
    }
}
