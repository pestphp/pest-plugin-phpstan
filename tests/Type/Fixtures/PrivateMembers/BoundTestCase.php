<?php

declare(strict_types=1);

namespace Tests\Type\Fixtures\PrivateMembers;

use PHPUnit\Framework\TestCase;

// @note: rector skips this directory, the private members are called from analysed fixtures and dead code
final class BoundTestCase extends TestCase
{
    private const string PRIVATE_CONSTANT = 'private';

    public function publicHelper(): string
    {
        return 'public';
    }

    protected function protectedHelper(): string
    {
        return 'protected';
    }

    private static function privateStaticHelper(): string
    {
        return 'private static';
    }

    private function privateHelper(): string
    {
        return 'private';
    }
}
