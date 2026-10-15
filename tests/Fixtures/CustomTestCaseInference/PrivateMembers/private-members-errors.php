<?php

declare(strict_types=1);

use Tests\Type\Fixtures\PrivateMembers\BoundTestCase;

uses(BoundTestCase::class);

it('reports private members declared by the bound test case', function (): void {
    $this->privateHelper();
    $this::privateStaticHelper();
    $x = $this::PRIVATE_CONSTANT;
});

it('reports private members reached through an arrow function', fn (): string => $this->privateHelper());

it('leaves private members of another object to phpstan', function (): void {
    $other = new BoundTestCase;

    $other->privateHelper();
    $other::privateStaticHelper();
});

it('leaves reachable members of the bound test case alone', function (): void {
    $this->protectedHelper();
    $this->publicHelper();
});

it('leaves private members inherited from a parent class to phpstan', function (): void {
    $this->runTest();
});

it('survives a dynamic member name on this', function (): void {
    $method = 'privateHelper';
    $constant = 'PRIVATE_CONSTANT';

    $this->$method();
    $x = $this::${$constant};
});
