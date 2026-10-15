<?php

declare(strict_types=1);

use Pest\Rector\Rules\Pest2ToPest3\UsesToExtendRector;
use Pest\Rector\Set\PestSetList;
use Rector\Config\RectorConfig;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withRootFiles()
    ->withSkip([
        ReadOnlyClassRector::class,
        __DIR__.'/tests/Type/data',
        __DIR__.'/tests/Rules/data',
        __DIR__.'/tests/Type/Fixtures/PrivateMembers',
        __DIR__.'/tests/Fixtures/CustomTestCaseInference',
        __DIR__.'/tests/Fixtures/UsesHookClosureThis',
        UsesToExtendRector::class => [
            __DIR__.'/tests/Type/Fixtures/pestconfig-matrix/Pest.php',
        ],
    ])
    ->withSets([
        PestSetList::CODING_STYLE,
    ])
    ->withImportNames(removeUnusedImports: true)
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
    );
