<?php

declare(strict_types=1);

namespace Pest\PHPStan\Analysis\Expectation;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Type;

final class ExpectationMatcherRegistry
{
    private readonly MatcherRequirementRegistry $requirementRegistry;

    private readonly MatcherAssertionRegistry $assertionRegistry;

    /** @var array<string, MatcherSemanticMetadata|null> */
    private array $metadataCache = [];

    public function __construct()
    {
        $this->requirementRegistry = new MatcherRequirementRegistry;
        $this->assertionRegistry = new MatcherAssertionRegistry;
    }

    public function assertedTypeFor(string $methodName, MethodCall $methodCall, Scope $scope): ?Type
    {
        return $this->assertionRegistry->assertedTypeFor($methodName, $methodCall, $scope);
    }

    public function assertsExactTypeFor(string $methodName, MethodCall $methodCall, Scope $scope): bool
    {
        return $this->assertionRegistry->assertsExactTypeFor($methodName, $methodCall, $scope);
    }

    public function metadataFor(string $methodName): ?MatcherSemanticMetadata
    {
        if (array_key_exists($methodName, $this->metadataCache)) {
            return $this->metadataCache[$methodName];
        }

        $requirement = $this->requirementRegistry->requirementFor($methodName);
        $assertion = $this->assertionRegistry->assertionFor($methodName);

        if ($requirement === null && $assertion === null) {
            $this->metadataCache[$methodName] = null;

            return null;
        }

        $this->metadataCache[$methodName] = new MatcherSemanticMetadata(
            $methodName,
            $requirement,
            $assertion,
        );

        return $this->metadataCache[$methodName];
    }
}
