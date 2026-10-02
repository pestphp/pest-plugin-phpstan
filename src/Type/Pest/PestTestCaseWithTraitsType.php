<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use Override;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ObjectType;

final class PestTestCaseWithTraitsType extends ObjectType
{
    /** @param  list<class-string>  $traitNames */
    public function __construct(
        string $className,
        private readonly array $traitNames,
        private readonly ReflectionProvider $reflectionProvider,
    ) {
        parent::__construct($className);
    }

    /** @return list<class-string> */
    public function getTraitNames(): array
    {
        return $this->traitNames;
    }

    #[Override]
    public function hasMethod(string $methodName): TrinaryLogic
    {
        if (parent::hasMethod($methodName)->yes()) {
            return TrinaryLogic::createYes();
        }

        if ($this->hasTraitMethod($methodName)) {
            return TrinaryLogic::createYes();
        }

        return parent::hasMethod($methodName);
    }

    #[Override]
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
    {
        if (parent::hasMethod($methodName)->yes()) {
            return parent::getMethod($methodName, $scope);
        }

        foreach ($this->traitNames as $traitName) {
            $traitReflection = $this->reflectionProvider->getClass($traitName);

            if ($traitReflection->hasNativeMethod($methodName)) {
                return $traitReflection->getNativeMethod($methodName);
            }
        }

        return parent::getMethod($methodName, $scope);
    }

    #[Override]
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
    {
        if (parent::hasMethod($methodName)->yes()) {
            return parent::getUnresolvedMethodPrototype($methodName, $scope);
        }

        foreach ($this->traitNames as $traitName) {
            if ($this->reflectionProvider->getClass($traitName)->hasNativeMethod($methodName)) {
                return new ObjectType($traitName)->getUnresolvedMethodPrototype($methodName, $scope);
            }
        }

        return parent::getUnresolvedMethodPrototype($methodName, $scope);
    }

    #[Override]
    public function hasProperty(string $propertyName): TrinaryLogic
    {
        if (parent::hasProperty($propertyName)->yes()) {
            return TrinaryLogic::createYes();
        }

        if ($this->hasTraitProperty($propertyName)) {
            return TrinaryLogic::createYes();
        }

        return parent::hasProperty($propertyName);
    }

    #[Override]
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
    {
        if (parent::hasProperty($propertyName)->yes()) {
            return parent::getProperty($propertyName, $scope);
        }

        foreach ($this->traitNames as $traitName) {
            $traitReflection = $this->reflectionProvider->getClass($traitName);

            if ($traitReflection->hasNativeProperty($propertyName)) {
                return $traitReflection->getNativeProperty($propertyName);
            }
        }

        return parent::getProperty($propertyName, $scope);
    }

    #[Override]
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
    {
        if (parent::hasProperty($propertyName)->yes()) {
            return parent::getUnresolvedPropertyPrototype($propertyName, $scope);
        }

        foreach ($this->traitNames as $traitName) {
            if ($this->reflectionProvider->getClass($traitName)->hasNativeProperty($propertyName)) {
                return new ObjectType($traitName)->getUnresolvedPropertyPrototype($propertyName, $scope);
            }
        }

        return parent::getUnresolvedPropertyPrototype($propertyName, $scope);
    }

    #[Override]
    public function hasConstant(string $constantName): TrinaryLogic
    {
        if (parent::hasConstant($constantName)->yes()) {
            return TrinaryLogic::createYes();
        }

        if ($this->hasTraitConstant($constantName)) {
            return TrinaryLogic::createYes();
        }

        return parent::hasConstant($constantName);
    }

    #[Override]
    public function getConstant(string $constantName): ClassConstantReflection
    {
        if (parent::hasConstant($constantName)->yes()) {
            return parent::getConstant($constantName);
        }

        foreach ($this->traitNames as $traitName) {
            $traitReflection = $this->reflectionProvider->getClass($traitName);

            if ($traitReflection->hasConstant($constantName)) {
                return $traitReflection->getConstant($constantName);
            }
        }

        return parent::getConstant($constantName);
    }

    private function hasTraitMethod(string $methodName): bool
    {
        return array_any($this->traitNames, fn (string $traitName): bool => $this->reflectionProvider->getClass($traitName)->hasNativeMethod($methodName));
    }

    private function hasTraitProperty(string $propertyName): bool
    {
        return array_any($this->traitNames, fn (string $traitName): bool => $this->reflectionProvider->getClass($traitName)->hasNativeProperty($propertyName));
    }

    private function hasTraitConstant(string $constantName): bool
    {
        return array_any($this->traitNames, fn (string $traitName): bool => $this->reflectionProvider->getClass($traitName)->hasConstant($constantName));
    }
}
