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

function testBareTestCallInHelperFunctionKeepsDeclaredType(): void
{
    assertType('Pest\\PendingCalls\\TestCall|Pest\\Support\\HigherOrderTapProxy', test());
}

function testBareTestCallInStaticClosureKeepsDeclaredType(): void
{
    it('keeps the declared type without $this', static function (): void {
        assertType('Pest\\PendingCalls\\TestCall|Pest\\Support\\HigherOrderTapProxy', test());
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
