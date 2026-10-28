<?php

declare(strict_types=1);

namespace Tests\Type\Fixtures;

use PHPUnit\Framework\TestCase;

final class CustomTodoTestCase extends TestCase
{
    public function todo(): string
    {
        return 'custom todo';
    }
}
