<?php

declare(strict_types=1);

namespace Tests\Rules;

use Pest\PHPStan\Rules\ImpossibleExpectationRule;
use Tests\RuleTestCase;

beforeAll(function (): void {
    RuleTestCase::$additionalConfigFiles = [
        __DIR__.'/../extension.neon',
    ];
    RuleTestCase::$rule = RuleTestCase::resolveRule(ImpossibleExpectationRule::class);
});

test('impossible expectations are reported', function (): void {
    $this->analyse([
        __DIR__.'/data/impossible-expectation.php',
    ], [
        [
            'Calling toBeString() on Expectation<int>; assertion is impossible.',
            6,
            'The expectation value is int, which can never satisfy toBeString().',
        ],
        [
            'Calling toBeInt() on Expectation<string>; assertion is impossible.',
            10,
            'The expectation value is string, which can never satisfy toBeInt().',
        ],
        [
            'Calling toBeFloat() on Expectation<string>; assertion is impossible.',
            14,
            'The expectation value is string, which can never satisfy toBeFloat().',
        ],
        [
            'Calling toBeBool() on Expectation<string>; assertion is impossible.',
            18,
            'The expectation value is string, which can never satisfy toBeBool().',
        ],
        [
            'Calling toBeTrue() on Expectation<int>; assertion is impossible.',
            22,
            'The expectation value is int, which can never satisfy toBeTrue().',
        ],
        [
            'Calling toBeFalse() on Expectation<int>; assertion is impossible.',
            26,
            'The expectation value is int, which can never satisfy toBeFalse().',
        ],
        [
            'Calling toBeNull() on Expectation<string>; assertion is impossible.',
            30,
            'The expectation value is string, which can never satisfy toBeNull().',
        ],
        [
            'Calling toBeArray() on Expectation<string>; assertion is impossible.',
            34,
            'The expectation value is string, which can never satisfy toBeArray().',
        ],
        [
            'Calling toBeObject() on Expectation<int>; assertion is impossible.',
            38,
            'The expectation value is int, which can never satisfy toBeObject().',
        ],
        [
            'Calling toBeIterable() on Expectation<int>; assertion is impossible.',
            42,
            'The expectation value is int, which can never satisfy toBeIterable().',
        ],
        [
            'Calling toBeCallable() on Expectation<null>; assertion is impossible.',
            46,
            'The expectation value is null, which can never satisfy toBeCallable().',
        ],
        [
            'Calling toBeInstanceOf() on Expectation<int>; assertion is impossible.',
            50,
            'The expectation value is int, which can never satisfy toBeInstanceOf().',
        ],
        [
            'Calling toBeScalar() on Expectation<array>; assertion is impossible.',
            54,
            'The expectation value is array, which can never satisfy toBeScalar().',
        ],
        [
            'Calling toBeNumeric() on Expectation<null>; assertion is impossible.',
            58,
            'The expectation value is null, which can never satisfy toBeNumeric().',
        ],
        [
            'Calling toBeInt() on Expectation<string>; assertion is impossible.',
            72,
            'The expectation value is string, which can never satisfy toBeInt().',
        ],
        [
            'Calling toBeInt() on Expectation<string>; assertion is impossible.',
            78,
            'The expectation value is string, which can never satisfy toBeInt().',
        ],
    ]);
});

test('impossible chains only report the first broken step', function (): void {
    $this->analyse([
        __DIR__.'/data/impossible-expectation-chain.php',
    ], [
        [
            'Calling toBeString() on Expectation<int>; assertion is impossible.',
            6,
            'The expectation value is int, which can never satisfy toBeString().',
        ],
    ]);
});

test('instanceof and numeric impossibilities are reported conservatively', function (): void {
    $this->analyse([
        __DIR__.'/data/impossible-expectation-instanceof.php',
    ], [
        [
            'Calling toBeInstanceOf() on Expectation<stdClass>; assertion is impossible.',
            12,
            'The expectation value is stdClass, which can never satisfy toBeInstanceOf().',
        ],
        [
            'Calling toBeInstanceOf() on Expectation<RuntimeException>; assertion is impossible.',
            16,
            'The expectation value is RuntimeException, which can never satisfy toBeInstanceOf().',
        ],
        [
            'Calling toBeNumeric() on Expectation<bool>; assertion is impossible.',
            23,
            'The expectation value is bool, which can never satisfy toBeNumeric().',
        ],
    ]);
});

test('impossible semantic chains suppress downstream matcher diagnostics', function (): void {
    $this->analyse([
        __DIR__.'/data/impossible-expectation-semantic-chain.php',
    ], [
        [
            'Calling toBeString() on Expectation<int>; assertion is impossible.',
            6,
            'The expectation value is int, which can never satisfy toBeString().',
        ],
        [
            'Calling toBeString() on Expectation<int>; assertion is impossible.',
            10,
            'The expectation value is int, which can never satisfy toBeString().',
        ],
    ]);
});

test('valid expectations from bool methods, casts, and nullable properties are not falsely flagged', function (): void {
    $this->analyse([
        __DIR__.'/data/impossible-expectation-bool-cast-nullable.php',
        __DIR__.'/data/impossible-expectation-primitive-types.php',
    ], []);
});

test('every impossible matcher combination is reported without false positives', function (): void {
    $this->analyse([
        __DIR__.'/data/impossible-expectation-exhaustive.php',
    ], [
        [
            'Calling toBeString() on Expectation<int>; assertion is impossible.',
            8,
            'The expectation value is int, which can never satisfy toBeString().',
        ],
        [
            'Calling toBeFloat() on Expectation<int>; assertion is impossible.',
            12,
            'The expectation value is int, which can never satisfy toBeFloat().',
        ],
        [
            'Calling toBeBool() on Expectation<int>; assertion is impossible.',
            16,
            'The expectation value is int, which can never satisfy toBeBool().',
        ],
        [
            'Calling toBeNull() on Expectation<int>; assertion is impossible.',
            20,
            'The expectation value is int, which can never satisfy toBeNull().',
        ],
        [
            'Calling toBeArray() on Expectation<int>; assertion is impossible.',
            24,
            'The expectation value is int, which can never satisfy toBeArray().',
        ],
        [
            'Calling toBeObject() on Expectation<int>; assertion is impossible.',
            28,
            'The expectation value is int, which can never satisfy toBeObject().',
        ],
        [
            'Calling toBeCallable() on Expectation<int>; assertion is impossible.',
            32,
            'The expectation value is int, which can never satisfy toBeCallable().',
        ],
        [
            'Calling toBeIterable() on Expectation<int>; assertion is impossible.',
            36,
            'The expectation value is int, which can never satisfy toBeIterable().',
        ],
        [
            'Calling toBeInt() on Expectation<string>; assertion is impossible.',
            40,
            'The expectation value is string, which can never satisfy toBeInt().',
        ],
        [
            'Calling toBeFloat() on Expectation<string>; assertion is impossible.',
            44,
            'The expectation value is string, which can never satisfy toBeFloat().',
        ],
        [
            'Calling toBeBool() on Expectation<string>; assertion is impossible.',
            48,
            'The expectation value is string, which can never satisfy toBeBool().',
        ],
        [
            'Calling toBeArray() on Expectation<string>; assertion is impossible.',
            52,
            'The expectation value is string, which can never satisfy toBeArray().',
        ],
        [
            'Calling toBeNull() on Expectation<string>; assertion is impossible.',
            56,
            'The expectation value is string, which can never satisfy toBeNull().',
        ],
        [
            'Calling toBeInt() on Expectation<float>; assertion is impossible.',
            60,
            'The expectation value is float, which can never satisfy toBeInt().',
        ],
        [
            'Calling toBeString() on Expectation<float>; assertion is impossible.',
            64,
            'The expectation value is float, which can never satisfy toBeString().',
        ],
        [
            'Calling toBeInt() on Expectation<true>; assertion is impossible.',
            68,
            'The expectation value is true, which can never satisfy toBeInt().',
        ],
        [
            'Calling toBeString() on Expectation<true>; assertion is impossible.',
            72,
            'The expectation value is true, which can never satisfy toBeString().',
        ],
        [
            'Calling toBeString() on Expectation<null>; assertion is impossible.',
            76,
            'The expectation value is null, which can never satisfy toBeString().',
        ],
        [
            'Calling toBeInt() on Expectation<null>; assertion is impossible.',
            80,
            'The expectation value is null, which can never satisfy toBeInt().',
        ],
        [
            'Calling toBeArray() on Expectation<null>; assertion is impossible.',
            84,
            'The expectation value is null, which can never satisfy toBeArray().',
        ],
        [
            'Calling toBeString() on Expectation<array>; assertion is impossible.',
            88,
            'The expectation value is array, which can never satisfy toBeString().',
        ],
        [
            'Calling toBeInt() on Expectation<array>; assertion is impossible.',
            92,
            'The expectation value is array, which can never satisfy toBeInt().',
        ],
        [
            'Calling toBeScalar() on Expectation<array>; assertion is impossible.',
            96,
            'The expectation value is array, which can never satisfy toBeScalar().',
        ],
        [
            'Calling toBeObject() on Expectation<array>; assertion is impossible.',
            100,
            'The expectation value is array, which can never satisfy toBeObject().',
        ],
        [
            'Calling toBeFalse() on Expectation<true>; assertion is impossible.',
            104,
            'The expectation value is true, which can never satisfy toBeFalse().',
        ],
        [
            'Calling toBeTrue() on Expectation<false>; assertion is impossible.',
            108,
            'The expectation value is false, which can never satisfy toBeTrue().',
        ],
        [
            'Calling toBeTrue() on Expectation<int>; assertion is impossible.',
            112,
            'The expectation value is int, which can never satisfy toBeTrue().',
        ],
        [
            'Calling toBeFalse() on Expectation<string>; assertion is impossible.',
            116,
            'The expectation value is string, which can never satisfy toBeFalse().',
        ],
        [
            'Calling toBeNumeric() on Expectation<true>; assertion is impossible.',
            120,
            'The expectation value is true, which can never satisfy toBeNumeric().',
        ],
        [
            'Calling toBeNumeric() on Expectation<null>; assertion is impossible.',
            124,
            'The expectation value is null, which can never satisfy toBeNumeric().',
        ],
        [
            'Calling toBeNumeric() on Expectation<array>; assertion is impossible.',
            128,
            'The expectation value is array, which can never satisfy toBeNumeric().',
        ],
        [
            'Calling toBeInstanceOf() on Expectation<int>; assertion is impossible.',
            132,
            'The expectation value is int, which can never satisfy toBeInstanceOf().',
        ],
        [
            'Calling toBeInstanceOf() on Expectation<string>; assertion is impossible.',
            136,
            'The expectation value is string, which can never satisfy toBeInstanceOf().',
        ],
        [
            'Calling toBeInstanceOf() on Expectation<stdClass>; assertion is impossible.',
            140,
            'The expectation value is stdClass, which can never satisfy toBeInstanceOf().',
        ],
        [
            'Calling toBeInstanceOf() on Expectation<Tests\\Type\\Fixtures\\Post>; assertion is impossible.',
            144,
            'The expectation value is Tests\\Type\\Fixtures\\Post, which can never satisfy toBeInstanceOf().',
        ],
    ]);
});

test('higher order method calls are supported in impossible expectation analysis', function (): void {
    $this->analyse([
        __DIR__.'/data/impossible-expectation-higher-order-methods.php',
    ], [
        [
            'Calling toBeInt() on Expectation<string>; assertion is impossible.',
            22,
            'The expectation value is string, which can never satisfy toBeInt().',
        ],
    ]);
});
