<?php

declare(strict_types=1);

namespace TestFunctionCustomTestCase;

use Pest\PendingCalls\TestCall;
use Tests\Type\Fixtures\CustomTestCase;
use Tests\Type\Fixtures\Post;

use function PHPStan\Testing\assertType;

function testBareTestCallInTestClosure(): void
{
    it('types a bare test() as the bound test case', function (): void {
        assertType(CustomTestCase::class, test());
    });
}

function testBareTestCallInBeforeEach(): void
{
    beforeEach(function (): void {
        assertType(CustomTestCase::class, test());
    });
}

function testBareTestCallInHelperFunction(): void
{
    assertType(CustomTestCase::class, test());
    assertType('string', test()->createHelper());
}

function testBareTestCallInStaticCallback(): void
{
    it('uses the bound type in static callbacks without $this', function (): void {
        $resolve = static fn () => test();

        assertType(CustomTestCase::class, $resolve());
    });
}

function testBareTestCallInNestedArrowFunction(): void
{
    it('types a bare test() inside a nested arrow function', function (): void {
        $resolve = fn () => test();

        assertType(CustomTestCase::class, $resolve());
    });
}

function testMethodCallThroughBareTestCall(): void
{
    it('resolves methods through a bare test()', function (): void {
        assertType('string', test()->createHelper());
    });
}

function testHookPropertyReadThroughBareTestCall(): void
{
    beforeEach(function (): void {
        test()->post = new Post;
    });

    it('resolves a hook property read through a bare test()', function (): void {
        assertType(Post::class, test()->post);
    });
}

function testTestCallWithDescriptionIsUnchanged(): void
{
    assertType(TestCall::class, test('has a description'));
}

function testTodoThroughBareTestCall(): void
{
    it('preserves the todo helper on the proxy', function (): void {
        assertType(TestCall::class, test()->todo('implement this test'));
    });
}
