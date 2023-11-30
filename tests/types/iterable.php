<?php

declare(strict_types=1);

use Rx\Observable;

use function PHPStan\Testing\assertType;
use function WyriHaximus\React\awaitObservable;

assertType('iterable<bool>', awaitObservable(Observable::fromArray([true, false])));
assertType('iterable<int>', awaitObservable(Observable::fromArray([time()])));
assertType('iterable<bool|int>', awaitObservable(Observable::fromArray([true, false, time()])));
