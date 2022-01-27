<?php

declare(strict_types=1);

namespace WyriHaximus\React;

use Rx\Observable;
use Throwable;

/**
 * @return iterable<mixed>
 */
function awaitObservable(Observable $observable): iterable
{
    $iterator = new AwaitingIterator();

    $observable->subscribe(
        static function (mixed $value) use ($iterator): void {
            $iterator->push($value);
        },
        static function (Throwable $throwable): void {
            throw $throwable;
        },
        static function () use ($iterator): void {
            $iterator->complete();
        },
    );

    return $iterator;
}
