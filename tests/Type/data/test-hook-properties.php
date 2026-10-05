<?php

declare(strict_types=1);

namespace TestHookProperties;

use Tests\TestCase;
use Tests\Type\Fixtures\Author;
use Tests\Type\Fixtures\Post;

use function PHPStan\Testing\assertType;

function someFunction(): mixed
{
    return fopen('php://memory', 'rb');
}

function resolvePost(): mixed
{
    return new Post;
}

function resolveAuthor(): mixed
{
    return new Author;
}

function testBeforeEachNewObject(): void
{
    beforeEach(function (): void {
        $this->post = new Post;
    });

    it('resolves property type from beforeEach', function (): void {
        assertType(Post::class, $this->post);
    });
}

function testBeforeEachMultipleProperties(): void
{
    beforeEach(function (): void {
        $this->post = new Post;
        $this->author = new Author;
    });

    it('resolves multiple property types', function (): void {
        assertType(Post::class, $this->post);
        assertType(Author::class, $this->author);
    });
}

function testBeforeEachStringLiteral(): void
{
    beforeEach(function (): void {
        $this->name = 'test';
    });

    it('resolves string type from beforeEach', function (): void {
        assertType("'test'", $this->name);
    });
}

function testBeforeEachIntLiteral(): void
{
    beforeEach(function (): void {
        $this->count = 42;
    });

    it('resolves int type from beforeEach', function (): void {
        assertType('42', $this->count);
    });
}

function testBeforeEachBoolLiteral(): void
{
    beforeEach(function (): void {
        $this->flag = true;
    });

    it('resolves bool type from beforeEach', function (): void {
        assertType('true', $this->flag);
    });
}

function testBeforeEachNullLiteral(): void
{
    beforeEach(function (): void {
        $this->empty = null;
    });

    it('resolves null type from beforeEach', function (): void {
        assertType('null', $this->empty);
    });
}

function testBeforeEachArrayLiteral(): void
{
    beforeEach(function (): void {
        $this->items = [];
    });

    it('resolves array type from beforeEach', function (): void {
        assertType('array{}', $this->items);
    });
}

function testUnknownPropertyStaysMixed(): void
{
    beforeEach(function (): void {
        $this->post = new Post;
    });

    it('returns mixed for properties not set in hooks', function (): void {
        assertType('mixed', $this->unknownProp);
    });
}

function testUnrecognizedExpressionStaysMixed(): void
{
    beforeEach(function (): void {
        $this->result = someFunction();
    });

    it('returns mixed for unrecognized expressions', function (): void {
        assertType('mixed', $this->result);
    });
}

function testDescribeScopedBeforeEach(): void
{
    describe('group', function (): void {
        beforeEach(function (): void {
            $this->post = new Post;
        });

        it('resolves property from a beforeEach nested inside describe', function (): void {
            assertType(Post::class, $this->post);
        });
    });
}

function testMultipleHooksSameProperty(): void
{
    beforeEach(function (): void {
        $this->item = new Post;
    });

    beforeEach(function (): void {
        $this->item = new Author;
    });

    it('unions types when multiple hooks set same property', function (): void {
        assertType('Tests\Type\Fixtures\Author|Tests\Type\Fixtures\Post', $this->item);
    });
}

function testBeforeEachFloatLiteral(): void
{
    beforeEach(function (): void {
        $this->price = 9.99;
    });

    it('resolves float type from beforeEach', function (): void {
        assertType('9.99', $this->price);
    });
}

function testBeforeEachVarAnnotation(): void
{
    beforeEach(function (): void {
        /** @var Post $post */
        $post = resolvePost();
        $this->post = $post;
    });

    it('resolves property type from @var PHPDoc annotation', function (): void {
        assertType(Post::class, $this->post);
    });
}

function testBeforeEachVarAnnotationWithoutVarName(): void
{
    beforeEach(function (): void {
        /** @var Author */
        $author = resolveAuthor();
        $this->author = $author;
    });

    it('resolves property type from @var PHPDoc without variable name', function (): void {
        assertType(Author::class, $this->author);
    });
}

function testBeforeEachVarAnnotationOnLocalVar(): void
{
    beforeEach(function (): void {
        /** @var Post $post */
        $post = resolvePost();
        $this->post = $post;
    });

    it('resolves property type from @var-annotated local variable', function (): void {
        assertType(Post::class, $this->post);
    });
}

function testBeforeEachMethodCallChain(): void
{
    beforeEach(function (): void {
        $this->post = Post::make();
    });

    it('resolves property type from method call chain without annotation', function (): void {
        assertType(Post::class, $this->post);
    });
}

function testEmptyBeforeEachHook(): void
{
    beforeEach(function (): void {});

    it('returns mixed for properties when hook is empty', function (): void {
        assertType('mixed', $this->anything);
    });
}

function testBeforeEachWithOnlyLocalStatements(): void
{
    beforeEach(function (): void {
        $local = new Post;
        $x = 42;
    });

    it('returns mixed when hook only has local variable assignments', function (): void {
        assertType('mixed', $this->local);
    });
}

function testVarThisAnnotationDoesNotOverridePropertyType(): void
{
    beforeEach(function (): void {
        /** @var TestCase $this */
        $this->post = new Post;
    });

    it('ignores @var $this and uses actual assigned type', function (): void {
        assertType(Post::class, $this->post);
    });
}

function testBeforeEachArrowFunctionAssignment(): void
{
    beforeEach(fn () => $this->arrowPost = new Post);

    it('resolves property type from an arrow function beforeEach', function (): void {
        assertType(Post::class, $this->arrowPost);
    });
}

function testBeforeEachArrowFunctionStringLiteral(): void
{
    beforeEach(fn () => $this->arrowName = 'test');

    it('resolves string type from an arrow function beforeEach', function (): void {
        assertType("'test'", $this->arrowName);
    });
}

function testBeforeEachArrowFunctionNonAssignmentStaysMixed(): void
{
    beforeEach(fn () => someFunction());

    it('returns mixed when an arrow function beforeEach does not assign a property', function (): void {
        assertType('mixed', $this->neverAssigned);
    });
}

function testBeforeEachNestedArrowFunctionStaysMixed(): void
{
    beforeEach(fn () => fn () => $this->nestedArrow = new Post);

    it('returns mixed when an arrow function beforeEach returns another arrow function', function (): void {
        assertType('mixed', $this->nestedArrow);
    });
}

function testBeforeEachSelfReferentialProperty(): void
{
    beforeEach(function (): void {
        $this->selfCounter = ($this->selfCounter ?? 0) + 1;
    });

    it('does not infinitely recurse when a hook reads the property it assigns', function (): void {
        assertType('(float|int)', $this->selfCounter);
    });
}

function testBeforeEachMutuallyReferentialProperties(): void
{
    beforeEach(function (): void {
        $this->cycleFirst = $this->cycleSecond;
        $this->cycleSecond = $this->cycleFirst;
    });

    it('does not infinitely recurse on a cycle across properties', function (): void {
        assertType('mixed', $this->cycleFirst);
    });
}

function testBeforeEachAssignmentThroughTestFunction(): void
{
    beforeEach(function (): void {
        test()->viaTestFunction = new Post;
    });

    it('resolves a property a hook sets through test()', function (): void {
        assertType(Post::class, $this->viaTestFunction);
    });
}

function testBeforeEachAssignmentThroughTestFunctionWithLocalVariable(): void
{
    beforeEach(function (): void {
        $author = new Author;
        test()->viaTestFunctionLocal = $author;
    });

    it('resolves a local variable a hook sets through test()', function (): void {
        assertType(Author::class, $this->viaTestFunctionLocal);
    });
}
