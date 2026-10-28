<?php

declare(strict_types=1);

use Tests\Type\Fixtures\CustomTodoTestCase;

use function PHPStan\Testing\assertType;

uses(CustomTodoTestCase::class);

it('prefers a native todo method over the proxy helper', function (): void {
    assertType('string', test()->todo());
});
