<?php

declare(strict_types=1);

namespace Tests\Fixtures\CustomTestCaseInference\PrivateMembers;

use Closure;
use PHPUnit\Framework\TestCase;

final class ClosureInsideTestCaseClass extends TestCase
{
    private const string OWN_PRIVATE_CONSTANT = 'own private';

    /**
     * @return Closure(): string
     */
    public function makeClosure(): Closure
    {
        return function (): string {
            return $this->ownPrivate().$this::OWN_PRIVATE_CONSTANT;
        };
    }

    private function ownPrivate(): string
    {
        return 'own private';
    }
}
