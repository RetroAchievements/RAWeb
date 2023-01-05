<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\CountOnNullRector;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->parallel();

    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/cronjobs',
        __DIR__ . '/database',
        __DIR__ . '/lib',
        __DIR__ . '/lang',
        __DIR__ . '/public',
        __DIR__ . '/resources',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_80,
    ]);

    $rectorConfig->skip([
        JsonThrowOnErrorRector::class,
        CountOnNullRector::class,
    ]);
};
