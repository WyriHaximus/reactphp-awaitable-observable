<?php

declare(strict_types=1);

namespace WyriHaximus\React;

use Rx\Observable;

/** @return iterable<mixed> */
function awaitObservable(Observable $observable): iterable
{
    return new AwaitingIterator($observable);
}
