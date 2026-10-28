<?php

declare(strict_types=1);

namespace TestFunctionMethodCalls;

use stdClass;

function helperUsingProtectedMethods(): string
{
    test()->createMock(stdClass::class);

    return test()->createHelper();
}

it('uses protected methods from static callbacks', function (): void {
    $resolve = static fn (): string => test()->createHelper();

    $resolve();
});

it('can use the todo helper through the proxy', function (): void {
    test()->todo('implement this test')->group('pending');
});
