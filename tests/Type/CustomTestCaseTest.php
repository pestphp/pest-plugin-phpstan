<?php

declare(strict_types=1);

use Tests\CustomTestCaseTestCase;

test('custom testcase closure types', function (string $assertType, string $file, mixed ...$args): void {
    $this->assertFileAsserts($assertType, $file, ...$args);
})->with(function (): Iterator {
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/Feature/test-closures-custom-testcase.php');
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/Feature/test-with-closures-custom-testcase.php');
});

test('a bare test() call types as the bound custom testcase', function (string $assertType, string $file, mixed ...$args): void {
    $this->assertFileAsserts($assertType, $file, ...$args);
})->with(function (): Iterator {
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/Feature/test-function-custom-testcase.php');
});

test('custom testcase closure types when a class and a trait are bound', function (string $assertType, string $file, mixed ...$args): void {
    $this->assertFileAsserts($assertType, $file, ...$args);
})->with(function (): Iterator {
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/WithTrait/with-trait-testcase.php');
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/WithTrait/with-trait-method-call.php');
});

test('custom testcase closure types from file-level uses()', function (string $assertType, string $file, mixed ...$args): void {
    $this->assertFileAsserts($assertType, $file, ...$args);
})->with(function (): Iterator {
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/LocalUses/local-uses-base.php');
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/LocalUses/local-uses-base-and-trait.php');
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/LocalUses/local-uses-trait-only.php');
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/LocalUses/local-uses-with-modifier.php');
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/LocalUses/local-pest-extend.php');
});

test('file-level uses() overrides the directory binding', function (string $assertType, string $file, mixed ...$args): void {
    $this->assertFileAsserts($assertType, $file, ...$args);
})->with(function (): Iterator {
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/Feature/local-uses-overrides-directory.php');
});

test('a trait-only file-level uses() keeps the directory binding', function (string $assertType, string $file, mixed ...$args): void {
    $this->assertFileAsserts($assertType, $file, ...$args);
})->with(function (): Iterator {
    yield from CustomTestCaseTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/CustomTestCaseInference/Feature/local-uses-trait-only-keeps-directory-testcase.php');
});
