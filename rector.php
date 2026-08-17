<?php

declare(strict_types=1);

use Pest\Rector\Set\PestSetList;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\CodingStyle\Rector\ClassMethod\MakeInheritedMethodVisibilitySameAsParentRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\MethodCall\RemoveNullArgOnNullDefaultParamRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

// `SafeDeclareStrictTypesRector` is skipped because Pint's `declare_strict_types` rule seeds
// the declaration instead; dropping that rule from `pint.json` would leave nothing adding it.
return RectorConfig::configure()
    ->withSets([
        PestSetList::CODING_STYLE,
    ])
    ->withCache(
        cacheDirectory: '/tmp/rector-filament-bouncer',
        cacheClass: FileCacheStorage::class,
    )
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
        MakeInheritedMethodVisibilitySameAsParentRector::class,
        AddOverrideAttributeToOverriddenPropertiesRector::class,
        SafeDeclareStrictTypesRector::class,
        // Filament's facade docblock claims a default for `setCurrentPanel()` that the real
        // method does not have, so this rule rewrites a required null argument away and the
        // call dies with an ArgumentCountError.
        RemoveNullArgOnNullDefaultParamRector::class => [
            __DIR__.'/tests/EventsTest.php',
        ],
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        earlyReturn: true,
    )
    ->withPhpSets();
