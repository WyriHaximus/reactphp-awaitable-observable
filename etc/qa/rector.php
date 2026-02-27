<?php

declare(strict_types=1);

use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use WyriHaximus\TestUtilities\RectorConfig;

return RectorConfig::configure(dirname(__DIR__, 2))->withSkip([
    ClassPropertyAssignToConstructorPromotionRector::class,
]);
