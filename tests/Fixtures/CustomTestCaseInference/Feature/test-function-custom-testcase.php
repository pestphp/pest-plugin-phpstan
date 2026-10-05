<?php

declare(strict_types=1);

namespace TestFunctionCustomTestCase;

use Pest\PendingCalls\TestCall;
use Tests\Type\Fixtures\CustomTestCase;

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
}

function testMethodCallThroughBareTestCall(): void
{
    it('resolves methods through a bare test()', function (): void {
        assertType('string', test()->createHelper());
    });
}

function testTestCallWithDescriptionIsUnchanged(): void
{
    assertType(TestCall::class, test('has a description'));
}
