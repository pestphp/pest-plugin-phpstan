<?php

declare(strict_types=1);

namespace Tests\Rules\Fixtures;

use Pest\Expectation;

interface CustomExpectations
{
    /**
     * @return Expectation<mixed>
     */
    public function toBeCustom(): Expectation;
}
