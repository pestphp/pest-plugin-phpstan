<?php

declare(strict_types=1);

namespace Tests\Type\Fixtures;

enum Role: string
{
    case Admin = 'admin';
    case Guest = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Guest => 'Guest',
        };
    }
}
