<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
        __DIR__.'/packages',
    ])
    ->withSkip([
        __DIR__.'/vendor',
        __DIR__.'/storage',
        __DIR__.'/bootstrap/cache',
        __DIR__.'/packages/*/vendor',

        // Skip specific rules that may cause issues
        LocallyCalledStaticMethodToNonStaticRector::class,
        DisallowedEmptyRuleFixerRector::class,
    ])
    ->withSets([
        // PHP version sets
        LevelSetList::UP_TO_PHP_84,

        // Code quality sets
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::INSTANCEOF,
    ])
    ->withRules([
        // Always add declare(strict_types=1)
        DeclareStrictTypesRector::class,

        // PHP 8.3+ override attribute
        AddOverrideAttributeToOverriddenMethodsRector::class,

        // Inline constructor defaults
        InlineConstructorDefaultToPropertyRector::class,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
        strictBooleans: true,
    )
    ->withPhpSets(php84: true)
    ->withImportNames(
        importNames: true,
        importDocBlockNames: true,
        importShortClasses: false,
        removeUnusedImports: true,
    );
