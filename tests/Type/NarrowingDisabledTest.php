<?php

declare(strict_types=1);

use Tests\NarrowingDisabledTestCase;

test('expectation narrowing can be turned off', function (string $assertType, string $file, mixed ...$args): void {
    $this->assertFileAsserts($assertType, $file, ...$args);
})->with(function (): Iterator {
    yield from NarrowingDisabledTestCase::gatherAssertTypes(__DIR__.'/data/expectation-narrowing-disabled.php');
});
