<?php

declare(strict_types=1);

namespace Tests\Type\Fixtures;

final class Author
{
    public string $name;

    public string $email;

    public function getName(): string
    {
        return $this->name;
    }
}
