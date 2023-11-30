<?php

declare(strict_types=1);

use Rx\Observable;
use Rx\Subject\Subject;

use function PHPStan\Testing\assertType;
use function WyriHaximus\React\awaitObservable;

assertType('iterable<bool>', awaitObservable(Observable::fromArray([true, false])));
assertType('iterable<int>', awaitObservable(Observable::fromArray([123, 456, 789])));
assertType('iterable<bool|string>', awaitObservable(Observable::fromArray([true, false, random_bytes(PHP_INT_SIZE), ''])));

/** @var Subject<stdClass> $subject */
$subject = new Subject();
assertType('iterable<stdClass>', awaitObservable($subject));
