<?php

declare(strict_types=1);

use Tests\GlobalTraitBindingTestCase;

test('a bare test() keeps the declared type when only global traits cover the file', function (string $assertType, string $file, mixed ...$args): void {
    $this->assertFileAsserts($assertType, $file, ...$args);
})->with(function (): Iterator {
    yield from GlobalTraitBindingTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/GlobalTraitBinding/modules/Billing/tests/Helpers.php');
    yield from GlobalTraitBindingTestCase::gatherAssertTypes(__DIR__.'/../Fixtures/GlobalTraitBinding/modules/Billing/tests/Feature/billing-test-function.php');
});
