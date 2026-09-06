<?php

declare(strict_types=1);

namespace Tests\Rules;

use PHPStan\Rules\Methods\CallMethodsRule;
use Tests\RuleTestCase;

beforeAll(function (): void {
    RuleTestCase::$additionalConfigFiles = [
        __DIR__.'/../extension.neon',
    ];
    RuleTestCase::$rule = RuleTestCase::resolveRule(CallMethodsRule::class);
});

test('higher order methods report errors on undefined methods on value type', function (): void {
    $this->analyse([
        __DIR__.'/data/higher-order-method-call-errors.php',
    ], [
        [
            'Call to an undefined method Pest\Expectation<Tests\Type\Fixtures\Post>::typoMethod().',
            18,
        ],
        [
            'Call to an undefined method Pest\Expectation<string>::foo().',
            20,
        ],
        [
            'Call to an undefined method Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Policy>, Tests\Type\Fixtures\Policy>::invalidMethod().',
            24,
        ],
    ]);
});
