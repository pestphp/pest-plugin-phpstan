<?php

declare(strict_types=1);

namespace Tests\Rules;

use PHPStan\Rules\Classes\ClassConstantRule;
use PHPStan\Rules\Methods\CallMethodsRule;
use PHPStan\Rules\Methods\CallStaticMethodsRule;
use Tests\RuleTestCase;

beforeAll(function (): void {
    RuleTestCase::$additionalConfigFiles = [
        __DIR__.'/../Type/custom-testcase-extension.neon',
    ];
});

test('protected and private trait methods are callable on $this in pest closures', function (): void {
    RuleTestCase::$rule = RuleTestCase::resolveRule(CallMethodsRule::class);

    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/TraitVisibility/trait-visibility-calls.php',
    ], []);
});

test('protected and private static trait methods are callable through $this in pest closures', function (): void {
    RuleTestCase::$rule = RuleTestCase::resolveRule(CallStaticMethodsRule::class);

    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/TraitVisibility/trait-visibility-calls.php',
    ], []);
});

test('private methods not flattened from a bound trait are still reported', function (): void {
    RuleTestCase::$rule = RuleTestCase::resolveRule(CallMethodsRule::class);

    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/TraitVisibility/trait-visibility-errors.php',
    ], [
        ['Call to private method runTest() of class PHPUnit\Framework\TestCase.', 12],
        ['Call to private method privateTraitMethod() of class Tests\Type\Fixtures\VisibilityTraitUser.', 18],
    ]);
});

test('private static trait methods called on another object are still reported', function (): void {
    RuleTestCase::$rule = RuleTestCase::resolveRule(CallStaticMethodsRule::class);

    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/TraitVisibility/trait-visibility-errors.php',
    ], [
        ['Call to private static method privateStaticTraitMethod() of class Tests\Type\Fixtures\VisibilityTraitUser.', 19],
    ]);
});

test('protected and private trait constants are readable through $this in pest closures', function (): void {
    RuleTestCase::$rule = RuleTestCase::resolveRule(ClassConstantRule::class);

    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/TraitVisibility/trait-visibility-calls.php',
    ], []);
});

test('private trait constants read from another object are still reported', function (): void {
    RuleTestCase::$rule = RuleTestCase::resolveRule(ClassConstantRule::class);

    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/TraitVisibility/trait-visibility-errors.php',
    ], [
        ['Access to private constant PRIVATE_TRAIT_CONSTANT of class Tests\\Type\\Fixtures\\VisibilityTraitUser.', 20],
    ]);
});
