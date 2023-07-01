<?php

declare(strict_types=1);

use Rector\Arguments\Rector\ClassMethod\ArgumentAdderRector;
use Rector\CodeQuality\Rector\Array_\CallableThisArrayToAnonymousFunctionRector;
use Rector\CodeQuality\Rector\Assign\CombinedAssignRector;
use Rector\CodeQuality\Rector\BooleanNot\SimplifyDeMorganBinaryRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;
use Rector\CodeQuality\Rector\For_\ForToForeachRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessLastVariableAssignRector;
use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\Identical\SimplifyConditionsRector;
use Rector\CodeQuality\Rector\If_\CombineIfRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodeQuality\Rector\If_\ShortenElseIfRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\CodeQuality\Rector\Ternary\SwitchNegatedTernaryRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Assign\RemoveUnusedVariableAssignRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveDelegatingParentCallRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\DeadCode\Rector\PropertyProperty\RemoveNullPropertyInitializationRector;
use Rector\DeadCode\Rector\Return_\RemoveDeadConditionAboveReturnRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\EarlyReturn\Rector\Foreach_\ChangeNestedForeachIfsToEarlyContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeAndIfToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeNestedIfsToEarlyReturnRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfContinueToMultiContinueRector;
use Rector\EarlyReturn\Rector\If_\ChangeOrIfReturnToEarlyReturnRector;
use Rector\Php71\Rector\FuncCall\CountOnNullRector;
use Rector\Php73\Rector\FuncCall\JsonThrowOnErrorRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ArrayShapeFromConstantArrayReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnDirectArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromReturnNewRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictTypedCallRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureReturnTypeRector;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {

    $rectorConfig->parallel();

    $rectorConfig->paths([
        __DIR__ . '/app',
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
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        LaravelSetList::LARAVEL_90,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
    ]);

    $rectorConfig->skip([
        __DIR__ . '/app/Helpers/util/recaptcha.php',

        // RemoveUnusedVariableAssignRector::class,

        // LevelSetList::UP_TO_PHP_XX
        ArgumentAdderRector::class,
        CountOnNullRector::class,
        JsonThrowOnErrorRector::class,

        // SetList::CODE_QUALITY
        CallableThisArrayToAnonymousFunctionRector::class,
        CombinedAssignRector::class,
        CombineIfRector::class,
        ExplicitBoolCompareRector::class, // TODO re-enable for PHPStan level 7+
        FlipTypeControlToUseExclusiveTypeRector::class,
        ForToForeachRector::class,
        SimplifyConditionsRector::class,
        SimplifyDeMorganBinaryRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
        SwitchNegatedTernaryRector::class,
        ShortenElseIfRector::class,
        SimplifyIfReturnBoolRector::class,
        SimplifyUselessLastVariableAssignRector::class,
        ReturnTypeFromStrictScalarReturnExprRector::class,

        // SetList::DEAD_CODE
        RemoveDeadConditionAboveReturnRector::class,
        RemoveDelegatingParentCallRector::class,
        RemoveEmptyClassMethodRector::class,
        RemoveNullPropertyInitializationRector::class,
        RemoveUnreachableStatementRector::class,
        RemoveUnusedPrivateMethodParameterRector::class,
        SimplifyUselessVariableRector::class,

        // SetList::EARLY_RETURN
        ChangeAndIfToEarlyReturnRector::class,
        ChangeNestedForeachIfsToEarlyContinueRector::class,
        ChangeNestedIfsToEarlyReturnRector::class,
        ChangeOrIfContinueToMultiContinueRector::class,
        ChangeOrIfReturnToEarlyReturnRector::class,

        // SetList::TYPE_DECLARATION,
        AddArrowFunctionReturnTypeRector::class,
        AddClosureReturnTypeRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        ArrayShapeFromConstantArrayReturnRector::class,
        ReturnNeverTypeRector::class,
        ReturnTypeFromReturnDirectArrayRector::class,
        ReturnTypeFromReturnNewRector::class,
        ReturnTypeFromStrictTypedCallRector::class, // TODO re-enable for PHPStan level 7+
    ]);
};
