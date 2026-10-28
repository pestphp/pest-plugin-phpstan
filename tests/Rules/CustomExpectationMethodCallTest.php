<?php

declare(strict_types=1);

namespace Tests\Rules;

use PHPStan\Rules\Methods\CallMethodsRule;
use Tests\RuleTestCase;

beforeAll(function (): void {
    RuleTestCase::$additionalConfigFiles = [
        __DIR__.'/custom-expectations.neon',
    ];
    RuleTestCase::$rule = RuleTestCase::resolveRule(CallMethodsRule::class);
});

test('custom expectations from a methods class reflection extension are not higher order method calls', function (): void {
    $this->analyse([
        __DIR__.'/data/custom-expectation-method-call.php',
    ], []);
});
