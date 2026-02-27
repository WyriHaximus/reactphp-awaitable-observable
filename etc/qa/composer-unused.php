<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

return static fn (Configuration $config): Configuration => $config->addNamedFilter(
    NamedFilter::fromString('wyrihaximus/react-event-loop-rx-scheduler-hook-up'),
);
