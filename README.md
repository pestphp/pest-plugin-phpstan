This repository contains the Pest Plugin for PHPStan.

> If you want to start testing your application with Pest, visit the main **[Pest Repository](https://github.com/pestphp/pest)**.

- Explore our docs at **[pestphp.com »](https://pestphp.com)**
- Follow the creator Nuno Maduro:
    - YouTube: **[youtube.com/@nunomaduro](https://www.youtube.com/@nunomaduro)** — Videos every weekday
    - Twitch: **[twitch.tv/enunomaduro](https://www.twitch.tv/enunomaduro)** — Streams (almost) every weekday
    - Twitter / X: **[x.com/enunomaduro](https://x.com/enunomaduro)**
    - LinkedIn: **[linkedin.com/in/nunomaduro](https://www.linkedin.com/in/nunomaduro)**
    - Instagram: **[instagram.com/enunomaduro](https://www.instagram.com/enunomaduro)**
    - Tiktok: **[tiktok.com/@enunomaduro](https://www.tiktok.com/@enunomaduro)**

## Type narrowing

Expectations narrow the types of the values they assert, in the expectation
chain and in the code that follows — just like `assert*` methods do in PHPUnit:

```php
function process(int|string $value): void
{
    expect($value)->toBeInt();

    // $value is int here
}
```

Narrowing understands chains (`->and($other)` switches to the other value),
negation (`->not->toBeNull()` removes `null`), identity (`->toBe(1)` narrows to
`1`) and loose equality (`->toEqual(1)` narrows to everything `== 1`). When a
chain transforms the value (`->json()`, `->each`, higher order expectations),
narrowing stops for the rest of that chain.

Pest is an open-sourced software licensed under the **[MIT license](https://opensource.org/licenses/MIT)**.
