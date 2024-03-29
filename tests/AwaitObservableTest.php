<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\React;

use Exception;
use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use Rx\Observable;
use Rx\Scheduler\ImmediateScheduler;
use Rx\Subject\Subject;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;
use WyriHaximus\React\AwaitingIterator;

use function range;
use function React\Async\async;
use function React\Async\await;
use function WyriHaximus\React\awaitObservable;

final class AwaitObservableTest extends AsyncTestCase
{
    /** @test */
    public function basic(): void
    {
        $observable = Observable::fromArray(range(0, 1337), new ImmediateScheduler());

        foreach (awaitObservable($observable) as $integer) {
            self::assertGreaterThanOrEqual(0, $integer);
            self::assertLessThanOrEqual(1337, $integer);
        }
    }

    /** @test */
    public function awaiting(): void
    {
        self::expectOutputString('tiktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktuktak');

        $observable = new Subject();

        // Put it in a timer because of how libuv works
        Loop::addTimer(0, static function () use ($observable): void {
            Loop::futureTick(static function () use ($observable): void {
                echo 'tik';
                Loop::futureTick(static function () use ($observable): void {
                    echo 'tik';
                    $observable->onNext(1);
                });
            });

            Loop::addTimer(0.1, static function () use ($observable): void {
                echo 'tik';
                $observable->onNext(2);
            });

            $count = 3;
            Loop::addPeriodicTimer(0.2, static function (TimerInterface $timer) use ($observable, &$count): void {
                echo 'tik';
                $observable->onNext($count++);

                if ($count <= 13) {
                    return;
                }

                echo 'tuk';
                Loop::cancelTimer($timer);
                $observable->onCompleted();
            });
        });

        $integers = [];
        await(async(static function () use ($observable, &$integers): void {
            foreach (awaitObservable($observable) as $integer) {
                echo 'tok';
                $integers[] = $integer;
            }

            echo 'tak';
        })());

        self::assertSame(range(1, 13), $integers);
    }

    /** @test */
    public function throw(): void
    {
        $error = new Exception('oops');
        self::expectException($error::class);
        self::expectExceptionMessage($error->getMessage());
        self::expectOutputString('tiktik');

        $observable = new Subject();

        Loop::futureTick(static function () use ($observable, $error): void {
            echo 'tik';
            Loop::futureTick(static function () use ($observable, $error): void {
                echo 'tik';
                $observable->onError($error);
            });
        });

        await(async(static function () use ($observable): void {
            foreach (awaitObservable($observable) as $void) {
                echo 'tok';
            }
        })());
    }

    /** @test */
    public function throwAfterSeveralItems(): void
    {
        $error = new Exception('oops');
        self::expectException($error::class);
        self::expectExceptionMessage($error->getMessage());
        self::expectOutputString('tiktoktiktoktiktoktiktoktuktak');

        $observable = new Subject();

        $count = 0;
        Loop::addPeriodicTimer(0.1, static function (TimerInterface $timer) use ($observable, &$count, $error): void {
            echo 'tik';
            $observable->onNext($count++);

            if ($count <= 3) {
                return;
            }

            echo 'tuk';
            Loop::cancelTimer($timer);
            echo 'tak';
            $observable->onError($error);
        });

        await(async(static function () use ($observable): void {
            foreach (awaitObservable($observable) as $void) {
                echo 'tok';
            }

            echo 'kat';
        })());
    }

    /** @test */
    public function break(): void
    {
        $observable = new Subject();
        $iterator   = new AwaitingIterator($observable);

        $count = 0;
        Loop::addPeriodicTimer(0.1, static function (TimerInterface $timer) use ($observable, &$count): void {
            $observable->onNext($count++);

            if ($count <= 13) {
                return;
            }

            Loop::cancelTimer($timer);
        });

        self::assertSame(3, await(async(static function () use ($iterator): int {
            $count = 0;
            foreach ($iterator as $void) {
                $count++;
                if ($count < 3) {
                    continue;
                }

                $iterator->break();
            }

            return $count;
        })()));
    }

    /** @test */
    public function emptyObservableThatTookAWhileToComplete(): void
    {
        $observable = new Subject();
        $iterator   = new AwaitingIterator($observable);

        Loop::addTimer(0.1, static function () use ($observable): void {
            $observable->onCompleted();
        });

        self::assertTrue(await(async(static function () use ($iterator): bool {
            foreach ($iterator as $void) {
                return false;
            }

            return true;
        })()));
    }
}
