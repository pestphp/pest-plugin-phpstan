<?php

declare(strict_types=1);

namespace NeverReceiver;

use Tests\Type\Fixtures\Post;

use function PHPStan\Testing\assertType;

/**
 * @param  never  $value
 */
function testPropertyFetchOnNever(mixed $value): void
{
    assertType('*ERROR*', $value->title);
}

/**
 * @param  never  $value
 */
function testMethodCallOnNever(mixed $value): void
{
    assertType('*ERROR*', $value->getTitle());
}

function testRealExpectationIsUnaffected(): void
{
    $post = new Post;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, string>', expect($post)->title);
}
