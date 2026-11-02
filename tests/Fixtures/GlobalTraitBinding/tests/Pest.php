<?php

declare(strict_types=1);

use Tests\Type\Fixtures\CustomTestCase;
use Tests\Type\Fixtures\HelperTrait;

// @note: a trait bound without in() applies to every test file, but binds no test case class.
pest()->use(HelperTrait::class);

pest()->extend(CustomTestCase::class)->in('../modules/*/tests/Feature');

// @note: a hook-only chain binds neither a class nor a trait.
pest()->beforeEach(function (): void {
    //
})->in('../modules/*/tests');
