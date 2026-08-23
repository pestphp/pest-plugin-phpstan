<?php

declare(strict_types=1);

namespace ExpectationNarrowingDisabled;

use RuntimeException;
use Tests\Type\Fixtures\Post;

use function PHPStan\Testing\assertType;

function testMatcherDoesNotNarrow(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->toBeInt();
    assertType('int|string', $value);
}

function testNegatedMatcherDoesNotNarrow(): void
{
    /** @var string|null $value */
    $value = random_int(0, 1) === 1 ? 'a' : null;
    expect($value)->not->toBeNull();
    assertType('string|null', $value);
}

function testComparisonDoesNotNarrow(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    expect($value)->toBe(1);
    assertType('int|string', $value);
}

function testInstanceOfDoesNotNarrowLaterInTheSameChain(mixed $subject): void
{
    expect($subject)->toBeInstanceOf(Post::class)
        ->and(assertType('mixed', $subject));
}

function testInstanceOfDoesNotNarrowPastTheStatement(mixed $subject): void
{
    expect($subject)->toBeInstanceOf(RuntimeException::class);
    assertType('mixed', $subject);
}

function testTheExpectationItselfStillCarriesItsValueType(): void
{
    /** @var int|string $value */
    $value = random_int(0, 1) === 1 ? 1 : 'a';
    $expectation = expect($value)->toBeInt();
    assertType('Pest\Expectation<int>', $expectation);
}
