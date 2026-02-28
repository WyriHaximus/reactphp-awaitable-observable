<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\React;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use React\EventLoop\Loop;
use ReflectionClass;
use Rx\Subject\Subject;
use stdClass;
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

    /** @return iterable<string, array<int, mixed>> */
    public static function provideAnythingButABool(): iterable
    {
        yield 'string' => ['string'];
        yield 'int' => [1];
        yield 'float' => [1.1];
        yield 'array' => [[1]];
        yield 'object' => [new stdClass()];
        yield 'null' => [null];
    }

    #[DataProvider('provideAnythingButABool')]
    #[Test]
    public function ensureValidNeverReturnsX(mixed $anythingButABool): void
    {
        $ai = new AwaitingIterator(new Subject());

        Loop::futureTick(static function () use ($ai, $anythingButABool): void {
            /** @phpstan-ignore method.nonObject */
            new ReflectionClass($ai)->getProperty('valid')->getValue($ai)->resolve($anythingButABool);
        });

        self::assertFalse($ai->valid());
    }
}
