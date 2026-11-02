<?php

declare(strict_types=1);

namespace GlobalTraitBindingHelpers;

use function PHPStan\Testing\assertType;

function helperOutsideAnyTestCaseBinding(): void
{
    assertType('Pest\PendingCalls\TestCall|Pest\Support\HigherOrderTapProxy', test());
}
