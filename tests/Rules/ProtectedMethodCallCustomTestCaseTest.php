<?php

declare(strict_types=1);

namespace Tests\Rules;

use PHPStan\Rules\Methods\CallMethodsRule;
use Tests\RuleTestCase;
use Tests\Type\Fixtures\CustomTestCase;

beforeAll(function (): void {
    RuleTestCase::$additionalConfigFiles = [
        __DIR__.'/../Type/custom-testcase-extension.neon',
    ];
    RuleTestCase::$rule = RuleTestCase::resolveRule(CallMethodsRule::class);
});

test('protected method calls are allowed with custom test case', function (): void {
    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/Feature/protected-method-calls-custom-testcase.php',
    ], []);
});

test('bare test calls allow protected methods and proxy helpers', function (): void {
    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/Feature/test-function-method-calls.php',
    ], []);
});

test('bare test calls still report undefined methods in helpers', function (): void {
    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/Feature/test-function-undefined-method.php',
    ], [
        ['Call to an undefined method '.CustomTestCase::class.'::createHelpre().', 9],
    ]);
});
