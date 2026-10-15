<?php

declare(strict_types=1);

use Tests\Type\Fixtures\PrivateMembers\BoundTestCase;
use Tests\Type\Fixtures\PrivateMembers\BoundTrait;

uses(BoundTestCase::class, BoundTrait::class);

it('leaves private members of a bound trait to the visibility extension', function (): void {
    $this->privateTraitHelper();
});
