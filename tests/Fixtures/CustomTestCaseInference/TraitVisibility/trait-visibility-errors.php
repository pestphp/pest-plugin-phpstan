<?php

declare(strict_types=1);

use Tests\Type\Fixtures\CustomTestCase;
use Tests\Type\Fixtures\VisibilityTrait;
use Tests\Type\Fixtures\VisibilityTraitUser;

uses(CustomTestCase::class, VisibilityTrait::class);

it('still reports private methods inherited from a parent class', function (): void {
    $this->runTest();
});

it('still reports private trait methods called on an object other than $this', function (): void {
    $other = new VisibilityTraitUser;

    $other->privateTraitMethod();
    $other::privateStaticTraitMethod();
    expect($other::PRIVATE_TRAIT_CONSTANT)->toBe('private');
});
