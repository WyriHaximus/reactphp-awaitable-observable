# [ReactPHP](https://github.com/reactphp/) awaitable observable

![Continuous Integration](https://github.com/wyrihaximus/reactphp-awaitable-observable/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/wyrihaximus/react-awaitable-observable/v/stable.png)](https://packagist.org/packages/wyrihaximus/react-awaitable-observable)
[![Total Downloads](https://poser.pugx.org/wyrihaximus/react-awaitable-observable/downloads.png)](https://packagist.org/packages/wyrihaximus/react-awaitable-observable/stats)
[![Type Coverage](https://shepherd.dev/github/WyriHaximus/reactphp-awaitable-observable/coverage.svg)](https://shepherd.dev/github/WyriHaximus/reactphp-awaitable-observable)
[![License](https://poser.pugx.org/wyrihaximus/react-awaitable-observable/license.png)](https://packagist.org/packages/wyrihaximus/react-awaitable-observable)

### Installation ###

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require wyrihaximus/react-awaitable-observable
```

## Usage ##

The `awaitObservable` function will accept any observable and turn it into an iterator, so it can be used inside
`async` in an `foreach`:

```php
use Rx\Observable;
use Rx\Scheduler\ImmediateScheduler;

use function React\Async\async;
use function WyriHaximus\React\awaitObservable;

async(function () {
    $observable = Observable::fromArray(range(0, 1337), new ImmediateScheduler());

    foreach (awaitObservable($observable) as $integer) {
        echo $integer; // outputs 01234....13361337
    }
});
```

## Breaking the iterator ##

The example above assumes you won't break the iterator. There are however situations where you want to short circuit
the iterator, for such situations the `break` method is provided.

```php
use Rx\Observable;
use Rx\Scheduler\ImmediateScheduler;

use function React\Async\async;
use function WyriHaximus\React\awaitObservable;

async(function () {
    $observable = Observable::fromArray(range(0, 1337), new ImmediateScheduler());

    $iterator = awaitObservable($observable);
    foreach ($iterator as $integer) {
        echo $integer; // outputs 01234
        if ($integer >= 4) {
            $iterator->break();
        }
    }
});
```

## Contributing ##

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## License ##

Copyright 2022 [Cees-Jan Kiewiet](https://wyrihaximus.net/)

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
