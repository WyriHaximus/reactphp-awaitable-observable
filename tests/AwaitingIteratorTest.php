<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\React;

use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\AwaitingIterator;

final class AwaitingIteratorTest extends AsyncTestCase
{
    /**
     * @test
     */
    public function keyNeverReturnsTheSameValue(): void
    {
        $ai = new AwaitingIterator();
        self::assertNotSame($ai->key(), $ai->key());
    }

    /**
     * @test
     */
    public function countsUpwards(): void
    {
        $ai = new AwaitingIterator();
        self::assertGreaterThan($ai->key(), $ai->key());
    }
}
