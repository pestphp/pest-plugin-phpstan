<?php

declare(strict_types=1);

use Tests\Type\Fixtures\Policy;
use Tests\Type\Fixtures\Post;

it('tests higher order methods', function (): void {
    $post = new Post;
    $policy = new Policy;

    expect($post)->getTitle()->toBe('Hello');

    expect($policy)
        ->view()->toBeTrue()
        ->update()->toBeTrue();

    expect($post)->typoMethod();

    expect('string')->foo();

    expect($policy)
        ->view()->toBeTrue()
        ->invalidMethod()->toBeTrue();
});
