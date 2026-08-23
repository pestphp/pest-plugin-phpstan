<?php

declare(strict_types=1);

namespace Tests\Type\Fixtures;

final class Post implements Archivable, Publishable
{
    public string $title;

    public string $content;

    public Author $author;

    public ?Author $editor = null;

    public static function make(): static
    {
        return new self;
    }

    public function publish(): void {}

    public function archive(): void {}
}
