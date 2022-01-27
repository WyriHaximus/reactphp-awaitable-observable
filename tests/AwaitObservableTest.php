<?php

declare(strict_types=1);

namespace WyriHaximus\Tests\React;

use React\EventLoop\Loop;
use React\EventLoop\TimerInterface;
use Rx\Observable;
use Rx\Scheduler\ImmediateScheduler;
use Rx\Subject\Subject;
use WyriHaximus\AsyncTestUtilities\AsyncTestCase;

use function range;
use function React\Async\async;
use function WyriHaximus\React\awaitObservable;

final class AwaitObservableTest extends AsyncTestCase
{
    /**
     * @test
     */
    public function basic(): void
    {
        $observable = Observable::fromArray(range(0, 1337), new ImmediateScheduler());

        foreach (awaitObservable($observable) as $integer) {
            self::assertGreaterThanOrEqual(0, $integer);
            self::assertLessThanOrEqual(1337, $integer);
        }
    }

    /**
     * @test
     */
    public function awaiting(): void
    {
        self::expectOutputString('tiktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktoktiktuktoktak');

        $observable = new Subject();

        Loop::futureTick(static function () use ($observable): void {
            echo 'tik';
            Loop::futureTick(static function () use ($observable): void {
                echo 'tik';
                $observable->onNext(1);
            });
        });

        Loop::addTimer(0.01, static function () use ($observable): void {
            echo 'tik';
            $observable->onNext(2);
        });

        $count = 3;
        Loop::addPeriodicTimer(0.02, static function (TimerInterface $timer) use ($observable, &$count): void {
            echo 'tik';
            $observable->onNext($count++);

            if ($count <= 13) {
                return;
            }

            echo 'tuk';
            Loop::cancelTimer($timer);
            $observable->onCompleted();
        });

        $integers = [];
        $this->await(async(static function () use ($observable, &$integers): void {
            foreach (awaitObservable($observable) as $integer) {
                echo 'tok';
                $integers[] = $integer;
            }

            echo 'tak';
        })());

        self::assertSame(range(1, 13), $integers);
    }
}
