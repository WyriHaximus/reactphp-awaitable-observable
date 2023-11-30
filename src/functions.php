<?php

declare(strict_types=1);

namespace WyriHaximus\React;

use Rx\Observable;

/**
 * @param Observable<T> $observable
 *
 * @return iterable<T>
 *
 * @template T
 */
function awaitObservable(Observable $observable): iterable
{
    return new AwaitingIterator($observable);
}
