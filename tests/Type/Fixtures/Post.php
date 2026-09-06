<?php

declare(strict_types=1);

namespace Tests\Type\Fixtures;

final class Post
{
    public string $title;

    public string $content;

    public Author $author;

    public ?Author $editor = null;

    public static function make(): static
    {
        return new self;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function belongsToAuthor(Author $author): bool
    {
        return $this->author === $author;
    }
}
