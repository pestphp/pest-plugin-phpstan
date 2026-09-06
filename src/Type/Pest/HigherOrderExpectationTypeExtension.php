<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use Pest\Arch\Contracts\ArchExpectation;
use Pest\Expectation;
use Pest\Expectations\HigherOrderExpectation;
use Pest\Expectations\OppositeExpectation;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\MissingPropertyFromReflectionException;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class HigherOrderExpectationTypeExtension implements ExpressionTypeResolverExtension
{
    /** @var list<string> */
    private const array EXPECTATION_KNOWN_PROPERTIES = ['each', 'classes', 'traits', 'interfaces', 'enums'];

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getType(Expr $expr, Scope $scope): ?Type
    {
        if ($expr instanceof PropertyFetch) {
            return $this->resolvePropertyFetch($expr, $scope);
        }

        if ($expr instanceof MethodCall) {
            return $this->resolveMethodCall($expr, $scope);
        }

        return null;
    }

    private function resolvePropertyFetch(PropertyFetch $expr, Scope $scope): ?Type
    {
        if (! $expr->name instanceof Identifier) {
            return null;
        }

        $propertyName = $expr->name->name;
        $varType = $scope->getType($expr->var);

        if ($propertyName === 'not') {
            $archType = new ObjectType(ArchExpectation::class);
            if ($archType->isSuperTypeOf($varType)->yes()) {
                $valueType = $this->extractTValueFromArchExpectationChain($expr, $scope);

                return new GenericObjectType(OppositeExpectation::class, [$valueType]);
            }
        }

        $expectationType = new ObjectType(Expectation::class);
        if ($expectationType->isSuperTypeOf($varType)->yes()) {
            return $this->resolveExpectationPropertyFetch($varType, $propertyName, $scope);
        }

        $higherOrderType = new ObjectType(HigherOrderExpectation::class);
        if ($higherOrderType->isSuperTypeOf($varType)->yes()) {
            return $this->resolveHigherOrderPropertyFetch($varType, $propertyName, $scope);
        }

        return null;
    }

    private function extractTValueFromArchExpectationChain(PropertyFetch $expr, Scope $scope): Type
    {
        if ($expr->var instanceof MethodCall) {
            $originalType = $scope->getType($expr->var->var);
            $tValue = $originalType->getTemplateType(Expectation::class, 'TValue');

            if (! $tValue instanceof MixedType) {
                return $tValue;
            }
        }

        return new ObjectType('string');
    }

    private function resolveExpectationPropertyFetch(Type $varType, string $propertyName, Scope $scope): ?Type
    {
        if ($propertyName === 'not') {
            $valueType = $varType->getTemplateType(Expectation::class, 'TValue');

            return new GenericObjectType(OppositeExpectation::class, [$valueType]);
        }

        if (in_array($propertyName, self::EXPECTATION_KNOWN_PROPERTIES, true)) {
            return null;
        }

        if ($this->isNativeExpectationProperty($propertyName)) {
            return null;
        }

        $valueType = $varType->getTemplateType(Expectation::class, 'TValue');

        $propertyType = $this->resolvePropertyType($valueType, $propertyName, $scope);
        if (! $propertyType instanceof Type) {
            return null;
        }

        return new GenericObjectType(HigherOrderExpectation::class, [
            new GenericObjectType(Expectation::class, [$valueType]),
            $propertyType,
        ]);
    }

    private function resolveHigherOrderPropertyFetch(Type $varType, string $propertyName, Scope $scope): ?Type
    {
        if ($propertyName === 'not') {
            return null;
        }

        $originalType = $varType->getTemplateType(HigherOrderExpectation::class, 'TOriginalValue');
        $currentValueType = $varType->getTemplateType(HigherOrderExpectation::class, 'TValue');

        $propertyType = $this->resolvePropertyType($currentValueType, $propertyName, $scope);
        if (! $propertyType instanceof Type) {
            return null;
        }

        return new GenericObjectType(HigherOrderExpectation::class, [
            $originalType,
            $propertyType,
        ]);
    }

    private function resolveMethodCall(MethodCall $expr, Scope $scope): ?Type
    {
        if (! $expr->name instanceof Identifier) {
            return null;
        }

        $methodName = $expr->name->name;
        $varType = $scope->getType($expr->var);

        $expectationType = new ObjectType(Expectation::class);
        if ($expectationType->isSuperTypeOf($varType)->yes()) {
            return $this->resolveExpectationMethodCall($varType, $methodName, $expr, $scope);
        }

        $higherOrderType = new ObjectType(HigherOrderExpectation::class);
        if ($higherOrderType->isSuperTypeOf($varType)->yes()) {
            return $this->resolveHigherOrderMethodCall($varType, $methodName, $expr, $scope);
        }

        return null;
    }

    private function resolveExpectationMethodCall(Type $varType, string $methodName, MethodCall $expr, Scope $scope): ?Type
    {
        if ($this->isKnownExpectationMethod($methodName)) {
            return null;
        }

        $valueType = $varType->getTemplateType(Expectation::class, 'TValue');

        $methodReturnType = $this->resolveMethodReturnType($valueType, $methodName, $expr, $scope);

        return new GenericObjectType(HigherOrderExpectation::class, [
            new GenericObjectType(Expectation::class, [$valueType]),
            $methodReturnType,
        ]);
    }

    private function resolveHigherOrderMethodCall(Type $varType, string $methodName, MethodCall $expr, Scope $scope): ?Type
    {
        if ($methodName === 'and') {
            return $this->resolveHigherOrderAndCall($expr, $scope);
        }

        if ($this->isNativeHigherOrderExpectationMethod($methodName)) {
            return null;
        }

        $originalType = $varType->getTemplateType(HigherOrderExpectation::class, 'TOriginalValue');

        if (Expectation::hasMethod($methodName)) {
            $originalValueType = $originalType->getTemplateType(Expectation::class, 'TValue');

            return new GenericObjectType(HigherOrderExpectation::class, [
                $originalType,
                $originalValueType,
            ]);
        }

        $currentValueType = $varType->getTemplateType(HigherOrderExpectation::class, 'TValue');

        $methodReturnType = $this->resolveMethodReturnType($currentValueType, $methodName, $expr, $scope);

        return new GenericObjectType(HigherOrderExpectation::class, [
            $originalType,
            $methodReturnType,
        ]);
    }

    private function resolveMethodReturnType(Type $objectType, string $methodName, MethodCall $expr, Scope $scope): Type
    {
        if ($objectType->hasMethod($methodName)->no()) {
            return new MixedType;
        }

        try {
            $methodReflection = $objectType->getMethod($methodName, $scope);

            $parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
                $scope,
                $expr->getArgs(),
                $methodReflection->getVariants(),
                $methodReflection->getNamedArgumentsVariants(),
            );

            $returnType = $parametersAcceptor->getReturnType();

            if ($returnType->isVoid()->yes()) {
                return new NullType;
            }

            return $returnType;
        } catch (MissingMethodFromReflectionException) {
            return new MixedType;
        }
    }

    private function resolveHigherOrderAndCall(MethodCall $expr, Scope $scope): ?Type
    {
        $args = $expr->getArgs();
        if ($args === []) {
            return null;
        }

        $newValueType = $scope->getType($args[0]->value);

        return new GenericObjectType(Expectation::class, [$newValueType]);
    }

    private function resolvePropertyType(Type $objectType, string $propertyName, Scope $scope): ?Type
    {
        if ($objectType->hasProperty($propertyName)->no()) {
            return null;
        }

        try {
            return $objectType->getProperty($propertyName, $scope)->getReadableType();
        } catch (MissingPropertyFromReflectionException) {
            return new MixedType;
        }
    }

    private function isNativeExpectationProperty(string $propertyName): bool
    {
        if (! $this->reflectionProvider->hasClass(Expectation::class)) {
            return false;
        }

        return $this->reflectionProvider->getClass(Expectation::class)
            ->hasNativeProperty($propertyName);
    }

    private function isNativeHigherOrderExpectationMethod(string $methodName): bool
    {
        if (! $this->reflectionProvider->hasClass(HigherOrderExpectation::class)) {
            return false;
        }

        return $this->reflectionProvider->getClass(HigherOrderExpectation::class)
            ->hasNativeMethod($methodName);
    }

    private function isKnownExpectationMethod(string $methodName): bool
    {
        if (Expectation::hasMethod($methodName)) {
            return true;
        }

        if (! $this->reflectionProvider->hasClass(Expectation::class)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass(Expectation::class);

        if ($classReflection->hasNativeMethod($methodName)) {
            return true;
        }

        foreach ($classReflection->getResolvedMixinTypes() as $mixinType) {
            foreach ($mixinType->getObjectClassReflections() as $mixinClassReflection) {
                if ($mixinClassReflection->hasNativeMethod($methodName)) {
                    return true;
                }
            }
        }

        return false;
    }
}
