<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\React;

use Rx\Subject\Subject;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\AwaitingIterator;

final class AwaitingIteratorTest extends AsyncTestCase
{
    /** @test */
    public function keyNeverReturnsTheSameValue(): void
    {
        $ai = new AwaitingIterator(new Subject());
        self::assertNotSame($ai->key(), $ai->key());
    }

    /** @test */
    public function countsUpwards(): void
    {
        $ai = new AwaitingIterator(new Subject());
        self::assertGreaterThan($ai->key(), $ai->key());
    }
}
