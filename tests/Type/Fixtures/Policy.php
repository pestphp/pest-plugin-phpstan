<?php

declare(strict_types=1);

namespace Tests\Type\Fixtures;

final class Policy
{
    public function view(): bool
    {
        return true;
    }

    public function update(): bool
    {
        return true;
    }
}
