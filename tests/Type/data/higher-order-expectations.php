<?php

declare(strict_types=1);

namespace HigherOrderExpectations;

use Tests\Type\Fixtures\Author;
use Tests\Type\Fixtures\MagicPropertyObject;
use Tests\Type\Fixtures\NonFinalObject;
use Tests\Type\Fixtures\Policy;
use Tests\Type\Fixtures\Post;
use Tests\Type\Fixtures\Role;

use function PHPStan\Testing\assertType;

function testPropertyAccessOnExpectation(): void
{
    $post = new Post;
    $result = expect($post)->title;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, string>', $result);
}

function testNestedPropertyAccess(): void
{
    $post = new Post;
    $result = expect($post)->editor;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, Tests\Type\Fixtures\Author|null>', $result);
}

function testChainedPropertyAfterAssertion(): void
{
    $post = new Post;
    $result = expect($post)
        ->title->toBe('Hello')
        ->content;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, string>', $result);
}

function testHigherOrderPropertyChain(): void
{
    $post = new Post;
    $result = expect($post)->author->name;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, string>', $result);
}

function testFullChainWithAssertions(): void
{
    $post = new Post;
    $result = expect($post)
        ->title->toBe('Title')
        ->content->toContain('content');
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, Tests\Type\Fixtures\Post>', $result);
}

function testAndThenHigherOrder(): void
{
    $post = new Post;
    $result = expect(true)->toBeTrue()
        ->and($post)
        ->title;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, string>', $result);
}

function testNullableValueProperty(): void
{
    /** @var Post|null $post */
    $post = null;
    $result = expect($post)->title;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post|null>, string>', $result);
}

function testNullableChain(): void
{
    /** @var Post|null $post */
    $post = null;
    $result = expect($post)
        ->title->toBe('Title')
        ->content;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post|null>, string>', $result);
}

function testNotPropertyPreservesType(): void
{
    /** @var Post|null $post */
    $post = null;
    $result = expect($post)->not;
    assertType('Pest\Expectations\OppositeExpectation<Tests\Type\Fixtures\Post|null>', $result);
}

function testNotToBeNullThenHigherOrder(): void
{
    /** @var Post|null $post */
    $post = null;
    $result = expect($post)
        ->not->toBeNull()
        ->title;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post|null>, string>', $result);
}

function testDeepPropertyChainThreeLevels(): void
{
    $post = new Post;
    $result = expect($post)->author->name;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, string>', $result);
}

function testPropertyChainAfterAssertionReset(): void
{
    $post = new Post;
    $result = expect($post)
        ->title->toBe('Hello')
        ->author;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, Tests\Type\Fixtures\Author>', $result);
}

function testMagicPropertyDoesNotCrash(): void
{
    $object = new MagicPropertyObject;
    $result = expect($object)->name;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\MagicPropertyObject>, mixed>', $result);
}

function testMagicPropertyChainDoesNotCrash(): void
{
    $object = new MagicPropertyObject;
    $result = expect($object)->name->toBe('Nuno');
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\MagicPropertyObject>, Tests\Type\Fixtures\MagicPropertyObject>', $result);
}

function testNonFinalClassMissingPropertyDoesNotCrash(NonFinalObject $object): void
{
    $result = expect($object)->name;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\NonFinalObject>, mixed>', $result);
}

function testNonFinalClassMissingPropertyChainDoesNotCrash(NonFinalObject $object): void
{
    $result = expect($object)->name->toBe('Nuno');
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\NonFinalObject>, Tests\Type\Fixtures\NonFinalObject>', $result);
}

function testNonFinalClassRealPropertyStillResolves(NonFinalObject $object): void
{
    $result = expect($object)->title;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\NonFinalObject>, string>', $result);
}

function testUnionWithoutClassPropertyDoesNotCrash(): void
{
    /** @var array<string, mixed>|object|null $payload */
    $payload = null;
    $result = expect($payload)->currentUser;
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<array<string, mixed>|object|null>, mixed>', $result);
}

function testDirectMethodCall(): void
{
    $post = new Post;
    $result = expect($post)->getTitle()->toBe('Hello');
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, Tests\Type\Fixtures\Post>', $result);
}

function testMethodCallWithArguments(): void
{
    $post = new Post;
    $author = new Author;
    $result = expect($post)->belongsToAuthor($author)->toBeTrue();
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, Tests\Type\Fixtures\Post>', $result);
}

function testMethodCallAfterPropertyFetch(): void
{
    $post = new Post;
    $result = expect($post)->author->getName()->toBe('Nuno');
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Post>, Tests\Type\Fixtures\Post>', $result);
}

function testEnumMethodCall(): void
{
    $result = expect(Role::Admin)->label()->toBe('Admin');
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Role::Admin>, Tests\Type\Fixtures\Role::Admin>', $result);
}

function testMultipleMethodCallsWithAssertionReset(): void
{
    $policy = new Policy;
    $result = expect($policy)
        ->view()->toBeTrue()
        ->update()->toBeTrue();
    assertType('Pest\Expectations\HigherOrderExpectation<Pest\Expectation<Tests\Type\Fixtures\Policy>, Tests\Type\Fixtures\Policy>', $result);
}
