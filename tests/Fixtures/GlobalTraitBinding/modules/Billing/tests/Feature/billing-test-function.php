<?php

declare(strict_types=1);

namespace GlobalTraitBindingFeature;

use Tests\Type\Fixtures\CustomTestCase;

use function PHPStan\Testing\assertType;

function helperInsideATestCaseBinding(): void
{
    assertType(CustomTestCase::class, test());
    assertType('string', test()->helperMethod());
}
