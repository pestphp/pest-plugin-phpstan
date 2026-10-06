<?php

declare(strict_types=1);

namespace Tests\Type\Fixtures\PrivateMembers;

trait BoundTrait
{
    private function privateTraitHelper(): string
    {
        return 'private trait';
    }
}
