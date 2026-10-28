<?php

declare(strict_types=1);

use Tests\Type\Fixtures\Post;

it('calls a custom expectation provided by a methods class reflection extension', function (): void {
    expect(Post::class)->toBeCustom();

    expect(new Post)->toBeCustom();

    expect('Hello')->toBeCustom();
});
