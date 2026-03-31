<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\React;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use React\EventLoop\Loop;
use React\Promise\PromiseInterface;
use ReflectionClass;
use Rx\Subject\Subject;
use SplQueue;
use stdClass;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\AwaitingIterator;

use function React\Async\async;
use function React\Async\await;
use function WyriHaximus\React\futurePromise;

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

    #[Test]
    public function breakDisposesSubscriptionSoFurtherUpstreamItemsAreIgnored(): void
    {
        $observable = new Subject();

        self::assertSame(
            ['first'],
            await(async(static function () use ($observable): array {
                $iterator = new AwaitingIterator($observable);
                $received = [];

                Loop::futureTick(static function () use ($observable): void {
                    $observable->onNext('first');
                });

                self::assertTrue($iterator->valid());
                $received[] = $iterator->current();

                /** @phpstan-ignore method.deprecated */
                $iterator->break();

                $observable->onNext('ignored-after-break');
                $observable->onNext('also-ignored');

                self::assertFalse($iterator->valid());
                self::assertFalse($observable->hasObservers());
                $value = new ReflectionClass($iterator)->getProperty('queue')->getValue($iterator);
                self::assertInstanceOf(SplQueue::class, $value);
                self::assertCount(0, $value);

                return $received;
            })()),
        );
    }

    #[Test]
    public function foreachBreakDestructDisposesSubscriptionSoFurtherUpstreamItemsAreIgnored(): void
    {
        $observable = new Subject();

        self::assertSame(
            ['first'],
            await(async(static function () use ($observable): array {
                $received = [];

                Loop::futureTick(static function () use ($observable): void {
                    $observable->onNext('first');
                });

                foreach (new AwaitingIterator($observable) as $value) {
                    $received[] = $value;
                    break;
                }

                await(futurePromise()->then(static fn (): PromiseInterface => futurePromise()));

                $observable->onNext('ignored-after-foreach-break');
                $observable->onNext('also-ignored');

                self::assertFalse($observable->hasObservers());

                return $received;
            })()),
        );
    }
}
