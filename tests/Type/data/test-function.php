<?php

declare(strict_types=1);

namespace TestFunction;

use function PHPStan\Testing\assertType;

function testBareTestCallWithoutBindingKeepsDeclaredType(): void
{
    it('keeps the declared type when no binding covers the file', function (): void {
        assertType('Pest\PendingCalls\TestCall|Pest\Support\HigherOrderTapProxy', test());
    });
}
