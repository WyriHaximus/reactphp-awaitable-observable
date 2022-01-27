<?php

declare(strict_types=1);

namespace WyriHaximus\React;

use function function_exists;

// @codeCoverageIgnoreStart
if (! function_exists(__NAMESPACE__ . '\\awaitObservable')) {
    require __DIR__ . '/functions.php';
}
