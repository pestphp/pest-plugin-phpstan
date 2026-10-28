<?php

declare(strict_types=1);

namespace Tests\Rules\Fixtures;

use Pest\Expectation;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ReflectionProvider;

final readonly class CustomExpectationsExtension implements MethodsClassReflectionExtension
{
    public function __construct(private ReflectionProvider $reflectionProvider) {}

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $classReflection->getName() === Expectation::class
            && $this->customExpectations()->hasNativeMethod($methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->customExpectations()->getNativeMethod($methodName);
    }

    private function customExpectations(): ClassReflection
    {
        return $this->reflectionProvider->getClass(CustomExpectations::class);
    }
}
