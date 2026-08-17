<?php

declare(strict_types=1);

namespace ExpectationNarrowing;

use LogicException;
use RuntimeException;

use function PHPStan\Testing\assertType;

function testToBeIntNarrows(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->toBeInt();
    assertType('int', $value);
}

function testToBeStringNarrows(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->toBeString();
    assertType('string', $value);
}

function testToBeNullNarrows(): void
{
    /** @var string|null $value */
    $value = random_int(0, 1) === 1 ? 'a' : null;
    expect($value)->toBeNull();
    assertType('null', $value);
}

function testToBeInstanceOfNarrows(): void
{
    $value = random_int(0, 1) === 1 ? new RuntimeException('x') : 'a';
    expect($value)->toBeInstanceOf(RuntimeException::class);
    assertType('RuntimeException', $value);
}

function testToBeTrueNarrows(): void
{
    $value = random_int(0, 1) === 1;
    expect($value)->toBeTrue();
    assertType('true', $value);
}

function testChainedMatchersAllNarrow(): void
{
    /** @var string|null $value */
    $value = random_int(0, 1) === 1 ? 'a' : null;
    expect($value)->toBeString()->toStartWith('a');
    assertType('string', $value);
}

function testAndRebindsTheSubject(): void
{
    /** @var int|string $first */
    $first = random_int(0, 1) === 1 ? 1 : 'a';
    /** @var int|string $second */
    $second = random_int(0, 1) === 1 ? 1 : 'a';
    expect($first)->toBeInt()->and($second)->toBeString();
    assertType('int', $first);
    assertType('string', $second);
}

function testWhenIsPassthrough(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->when(true, fn ($e) => $e)->toBeInt();
    assertType('int', $value);
}

function testJsonBreaksTheSubjectLink(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->toBeString()->json()->toBeArray();
    assertType('string', $value);
}

function testEachDoesNotNarrowTheSubject(): void
{
    /** @var array<int|string> $values */
    $values = [];
    expect($values)->each->toBeInt();
    assertType('array<int|string>', $values);
}

function testHigherOrderPropertyDoesNotNarrow(): void
{
    /** @var int|string $value Stays wide: the extension never fires on higher order chains */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->toBeInt()->foo->toBeString();
    assertType('int|string', $value);
}

function testExpectWithoutArgumentsIsIgnored(): void
{
    expect()->toBeNull();
}

function testAssignedExpectationAlsoNarrowsTheSubject(): void
{
    /** @var int|string $value Narrowing also applies when the chain is an assigned expression */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    $expectation = expect($value)->toBeInt();
    assertType('Pest\Expectation<int>', $expectation);
    assertType('int', $value);
}

function testNotToBeNullRemovesNull(): void
{
    /** @var string|null $value */
    $value = random_int(0, 1) === 1 ? 'a' : null;
    expect($value)->not->toBeNull();
    assertType('string', $value);
}

function testNotMethodRemovesNull(): void
{
    /** @var string|null $value */
    $value = random_int(0, 1) === 1 ? 'a' : null;
    expect($value)->not()->toBeNull();
    assertType('string', $value);
}

function testNotAppliesToOneMatcherOnly(): void
{
    /** @var string|null $value */
    $value = random_int(0, 1) === 1 ? 'a' : null;
    expect($value)->not->toBeNull()->toBeString();
    assertType('string', $value);
}

function testNotToBeInstanceOfRemovesTheClass(): void
{
    $value = random_int(0, 1) === 1 ? new RuntimeException('x') : 'a';
    expect($value)->not->toBeInstanceOf(RuntimeException::class);
    assertType("'a'", $value);
}

function testNotToBeStringOnUnion(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->not->toBeString();
    assertType('int', $value);
}

function testToBeNarrowsToTheComparedConstant(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->toBe(1);
    assertType('1', $value);
}

function testToBeNarrowsToTheComparedExpressionType(): void
{
    $value = random_int(0, 1) === 1 ? new RuntimeException('x') : 'a';
    $expected = new RuntimeException('x');
    expect($value)->toBe($expected);
    assertType('RuntimeException', $value);
}

function testNotToBeRemovesTheConstant(): void
{
    /** @var 'a'|'b' $value */
    $value = 'a';
    expect($value)->not->toBe('a');
    assertType("'b'", $value);
}

function testToEqualNarrowsLoosely(): void
{
    /** @var string|null $value Loose == against null also matches the empty string */
    $value = random_int(0, 1) === 1 ? 'a' : null;
    expect($value)->toEqual(null);
    assertType("''|null", $value);
}

function testNotFollowedByAndRebindDoesNotLeakNegation(): void
{
    /** @var string|null $a */
    $a = random_int(0, 1) === 1 ? 'a' : null;
    /** @var int|string $b */
    $b = random_int(0, 1) === 1 ? 1 : 'x';
    expect($a)->not->toBeNull()->and($b)->toBeInt();
    assertType('string', $a);
    assertType('int', $b);
}

function testDoubleNegationCancelsOut(): void
{
    /** @var string|null $value Pest throws on not->not, so this narrowing is vacuous */
    $value = random_int(0, 1) === 1 ? 'a' : null;
    expect($value)->not->not->toBeNull();
    assertType('null', $value);
}

function testToBeWithNoArgumentsDoesNotNarrow(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->toBe();
    assertType('int|string', $value);
}

function testNotToBeInstanceOfWithClassStringVariableDoesNotNarrow(): void
{
    /** @var RuntimeException|string $value */
    $value = random_int(0, 1) === 1 ? new RuntimeException('x') : 'a';
    /** @var class-string $class Unknown class: any object may still pass the negated matcher */
    $class = RuntimeException::class;
    expect($value)->not->toBeInstanceOf($class);
    assertType('RuntimeException|string', $value);
}

function testNotToBeInstanceOfWithMultipleClassStringsDoesNotNarrow(): void
{
    /** @var LogicException|RuntimeException|string $value Only one of the two classes is checked at runtime */
    $value = random_int(0, 1) === 1 ? new RuntimeException('x') : 'a';
    $class = random_int(0, 1) === 1 ? RuntimeException::class : LogicException::class;
    expect($value)->not->toBeInstanceOf($class);
    assertType('LogicException|RuntimeException|string', $value);
}

function testToBeInstanceOfWithClassStringVariableNarrowsToObject(): void
{
    /** @var RuntimeException|string $value */
    $value = random_int(0, 1) === 1 ? new RuntimeException('x') : 'a';
    /** @var class-string $class Positive narrowing may over-approximate to any object and stay sound */
    $class = RuntimeException::class;
    expect($value)->toBeInstanceOf($class);
    assertType('RuntimeException', $value);
}

function testAndWithExpectationArgumentStopsNarrowing(): void
{
    /** @var int|string $first */
    $first = random_int(0, 1) === 1 ? 1 : 'a';
    /** @var int|string $second and() rebinds to the inner value of the passed expectation */
    $second = random_int(0, 1) === 1 ? 1 : 'a';
    $expectation = expect($second);
    expect($first)->toBeInt()->and($expectation)->toBeString();
    assertType('int', $first);
    assertType('int|string', $second);
    assertType('Pest\Expectation<int|string>', $expectation);
}

function testAndWithInlineExpectationStopsNarrowing(): void
{
    /** @var int|string $first */
    $first = random_int(0, 1) === 1 ? 1 : 'a';
    /** @var int|string $second and() rebinds to the inner value, so the printed argument is not the subject */
    $second = random_int(0, 1) === 1 ? 1 : 'a';
    expect($first)->toBeInt()->and(expect($second))->toBeString();
    assertType('int', $first);
    assertType('int|string', $second);
}

function testNamedComparisonArgumentIsReadByName(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->toBe(message: 'not one', expected: 1);
    assertType('1', $value);
}

function testComparisonWithoutTheComparedValueDoesNotNarrow(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->toBe(message: 'the compared value is missing');
    assertType('int|string', $value);
}

function testNamedInstanceOfArgumentIsReadByName(): void
{
    /** @var RuntimeException|string $value */
    $value = random_int(0, 1) === 1 ? new RuntimeException('x') : 'a';
    expect($value)->not->toBeInstanceOf(message: 'still an exception', class: RuntimeException::class);
    assertType('string', $value);
}
