<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\React;

use PHPUnit\Framework\Attributes\Test;
use Rx\Subject\Subject;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\AwaitingIterator;

final class AwaitingIteratorTest extends AsyncTestCase
{
    #[Test]
    public function keyNeverReturnsTheSameValue(): void
    {
        $ai = new AwaitingIterator(new Subject());
        self::assertNotSame($ai->key(), $ai->key());
    }

    #[Test]
    public function countsUpwards(): void
    {
        $ai = new AwaitingIterator(new Subject());
        self::assertGreaterThan($ai->key(), $ai->key());
    }
}
