<?php

declare(strict_types=1);

namespace Tests\Rules;

use Pest\PHPStan\Rules\BoundTestCasePrivateMemberRule;
use Tests\RuleTestCase;

beforeAll(function (): void {
    RuleTestCase::$additionalConfigFiles = [
        __DIR__.'/../extension.neon',
    ];
    RuleTestCase::$rule = RuleTestCase::resolveRule(BoundTestCasePrivateMemberRule::class);
});

test('private members of the bound test case are reported', function (): void {
    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/PrivateMembers/private-members-errors.php',
    ], [
        ['Call to private method privateHelper() of class Tests\Type\Fixtures\PrivateMembers\BoundTestCase.', 10],
        ['Call to private static method privateStaticHelper() of class Tests\Type\Fixtures\PrivateMembers\BoundTestCase.', 11],
        ['Access to private constant PRIVATE_CONSTANT of class Tests\Type\Fixtures\PrivateMembers\BoundTestCase.', 12],
        ['Call to private method privateHelper() of class Tests\Type\Fixtures\PrivateMembers\BoundTestCase.', 15],
    ]);
});

test('private members of a trait bound through uses() are left alone', function (): void {
    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/PrivateMembers/private-members-bound-trait.php',
    ], []);
});

test('private members of the default test case are reported', function (): void {
    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/PrivateMembers/private-members-default-testcase.php',
    ], [
        ['Call to private method runTest() of class PHPUnit\Framework\TestCase.', 6],
    ]);
});

test('a closure declared inside a class keeps its own scope', function (): void {
    $this->analyse([
        __DIR__.'/../Fixtures/CustomTestCaseInference/PrivateMembers/closure-inside-class.php',
    ], []);
});
