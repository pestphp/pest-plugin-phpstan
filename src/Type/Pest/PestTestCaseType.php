<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPUnit\Framework\TestCase;

final class PestTestCaseType
{
    public function __construct(
        private readonly PestConfigReader $pestConfigReader,
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function resolve(string $filePath): Type
    {
        return $this->resolveIfBound($filePath) ?? new ObjectType(TestCase::class);
    }

    public function resolveIfBound(string $filePath): ?Type
    {
        // @note: returns null when no binding covers the file, so callers can tell an unbound file from one bound to PHPUnit's TestCase.
        [$classNames, $traitNames] = $this->resolveFileClassAndTraitNames($filePath);

        if ($classNames === [] && $traitNames === []) {
            return null;
        }

        if ($classNames === []) {
            $classNames[] = TestCase::class;
        }

        return $this->toTestCaseType($classNames, $traitNames);
    }

    public function resolveIfClassBound(string $filePath): ?Type
    {
        // @note: returns null unless a binding covers the file with a test case class, as traits bound without in() also cover files that are not tests.
        [$classNames, $traitNames] = $this->resolveFileClassAndTraitNames($filePath);

        if ($classNames === []) {
            return null;
        }

        return $this->toTestCaseType($classNames, $traitNames);
    }

    /**
     * @param  list<string>  $bindings
     */
    public function resolveFromBindings(array $bindings): ?Type
    {
        // @note: returns null when no class is bound, so callers can fall back to file based resolution.
        [$classNames, $traitNames] = $this->partition($bindings);

        if ($classNames === []) {
            return null;
        }

        return $this->toTestCaseType($classNames, $traitNames);
    }

    /**
     * @return array{list<class-string>, list<class-string>}
     */
    private function resolveFileClassAndTraitNames(string $filePath): array
    {
        [$classNames, $traitNames] = $this->partition(
            $this->pestConfigReader->resolveFileBindings($filePath),
        );

        if ($classNames === []) {
            [$classNames, $directoryTraitNames] = $this->partition(
                $this->pestConfigReader->resolveBindings($filePath),
            );

            $traitNames = array_values(array_unique([...$directoryTraitNames, ...$traitNames]));
        }

        return [$classNames, $traitNames];
    }

    /**
     * @param  non-empty-list<class-string>  $classNames
     * @param  list<class-string>  $traitNames
     */
    private function toTestCaseType(array $classNames, array $traitNames): Type
    {
        if ($traitNames === []) {
            return $this->toObjectType($classNames);
        }

        return new PestTestCaseWithTraitsType(
            $classNames[0],
            $traitNames,
            $this->reflectionProvider,
        );
    }

    /**
     * @param  list<string>  $bindings
     * @return array{list<class-string>, list<class-string>}
     */
    private function partition(array $bindings): array
    {
        $classNames = [];
        $traitNames = [];

        foreach ($bindings as $binding) {
            if (! $this->reflectionProvider->hasClass($binding)) {
                continue;
            }

            if ($this->reflectionProvider->getClass($binding)->isTrait()) {
                $traitNames[] = $binding;

                continue;
            }

            $classNames[] = $binding;
        }

        return [$classNames, $traitNames];
    }

    /**
     * @param  list<string>  $classNames
     */
    private function toObjectType(array $classNames): Type
    {
        if (count($classNames) === 1) {
            return new ObjectType($classNames[0]);
        }

        return TypeCombinator::intersect(...array_map(
            static fn (string $className): ObjectType => new ObjectType($className),
            $classNames,
        ));
    }
}
