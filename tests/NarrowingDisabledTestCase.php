<?php

declare(strict_types=1);

namespace Tests;

use Override;
use PHPStan\Testing\TypeInferenceTestCase;

abstract class NarrowingDisabledTestCase extends TypeInferenceTestCase
{
    /**
     * @return string[]
     */
    #[Override]
    final public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/Type/narrowing-disabled.neon',
        ];
    }
}
