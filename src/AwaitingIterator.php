<?php

declare(strict_types=1);

namespace WyriHaximus\React;

use Deprecated;
use Iterator;
use React\EventLoop\Loop;
use React\Promise\Deferred;
use Rx\DisposableInterface;
use Rx\ObservableInterface;
use SplQueue;
use Throwable;
use WeakReference;

use function is_bool;
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

    /** @var ObservableInterface<T>|null */
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

    public function __destruct()
    {
        if ($this->completed) {
            return;
        }

        $disposable = $this->disposable;
        $valid      = $this->valid;

        $this->completed  = true;
        $this->valid      = null;
        $this->disposable = null;

        Loop::futureTick(static function () use ($disposable, $valid): void {
            $disposable?->dispose();

            if (! $valid instanceof Deferred) {
                return;
            }

            $valid->resolve(false);
        });
    }

    /** @api */
    #[Deprecated(message: 'With the __destruct() method this is no longer needed.')]
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
            $weakSelf         = WeakReference::create($this);
            $this->disposable = $observable?->subscribe(
                static function (mixed $value) use ($weakSelf): void {
                    $self = $weakSelf->get();
                    if ($self === null) {
                        return;
                    }

                    $self->push($value);
                },
                static function (Throwable $throwable): never {
                    throw $throwable;
                },
                static function () use ($weakSelf): void {
                    $self = $weakSelf->get();
                    if ($self === null) {
                        return;
                    }

                    $self->complete();
                },
            );
        }

        if ($this->queue->count() > 0) {
            return true;
        }

        if (! $this->completed) {
            /** @var Deferred<bool> $valid */
            $valid       = new Deferred();
            $this->valid = $valid;

            $isValid = await($valid->promise());
            /** @phpstan-ignore function.alreadyNarrowedType */
            if (! is_bool($isValid)) {
                $isValid = false;
            }

            return $isValid;
        }

        return false;
    }

    public function rewind(): void
    {
        // no-op
    }
}
