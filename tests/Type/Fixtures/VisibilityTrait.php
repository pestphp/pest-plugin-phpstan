<?php

declare(strict_types=1);

namespace Tests\Type\Fixtures;

trait VisibilityTrait
{
    protected const string PROTECTED_TRAIT_CONSTANT = 'protected';

    private const string PRIVATE_TRAIT_CONSTANT = 'private';

    protected static function protectedStaticTraitMethod(): string
    {
        return 'protected static';
    }

    protected function protectedTraitMethod(): string
    {
        return 'protected';
    }

    private static function privateStaticTraitMethod(): string
    {
        return 'private static';
    }

    private function privateTraitMethod(): string
    {
        return 'private';
    }
}
