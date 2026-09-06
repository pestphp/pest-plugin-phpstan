<?php

declare(strict_types=1);

use Tests\Type\Fixtures\Author;
use Tests\Type\Fixtures\Post;
use Tests\Type\Fixtures\Role;

it('valid higher-order method calls are not impossible', function (): void {
    $post = new Post;
    $author = new Author;

    expect($post)->getTitle()->toBe('Hello');
    expect($post)->belongsToAuthor($author)->toBeTrue();
    expect($post)->author->getName()->toBe('Nuno');
    expect(Role::Admin)->label()->toBe('Admin');
});

it('impossible assertions on higher-order method calls are reported', function (): void {
    $post = new Post;

    expect($post)->getTitle()->toBeInt();
});
