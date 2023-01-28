<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Laravel\Set\LaravelSetList;
use Rector\Php71\Rector\FuncCall\CountOnNullRector;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->parallel();

    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/app_legacy',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/lang',
        __DIR__ . '/public',
        __DIR__ . '/resources',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->rules([
        InlineConstructorDefaultToPropertyRector::class,
    ]);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_80,
        SetList::ACTION_INJECTION_TO_CONSTRUCTOR_INJECTION,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::NAMING,
        SetList::PRIVATIZATION,
        SetList::PSR_4,
        SetList::TYPE_DECLARATION,
        LaravelSetList::LARAVEL_80,
        LaravelSetList::LARAVEL_90,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
    ]);

    $rectorConfig->skip([
        // AddArgumentDefaultValueRector::class,
        // ArgumentAdderRector::class,
        // ClassOnObjectRector::class,
        // StaticCallOnNonStaticToInstanceCallRector::class,
        // FirstClassCallableRector::class,
        // RemoveNonExistingVarAnnotationRector::class,
        // RemoveLastReturnRector::class,
        // ChangeReadOnlyVariableWithDefaultValueToConstantRector::class,
        // ClassPropertyAssignToConstructorPromotionRector::class,
        // ClosureToArrowFunctionRector::class => [
        //     __DIR__ . '/app/...php',
        //     __DIR__ . '/app_legacy/...php',
        // ],
        // IssetOnPropertyObjectToPropertyExistsRector::class,
        // MixedTypeRector::class,
        // RecastingRemovalRector::class,
        // RemoveDelegatingParentCallRector::class,
        // RemoveUselessParamTagRector::class,
        // RemoveUselessReturnTagRector::class,
        // RemoveUselessVarTagRector::class,
        // RenamePropertyRector::class,
        // UnionTypesRector::class,

        JsonThrowOnErrorRector::class,
        CountOnNullRector::class,
    ]);
};
