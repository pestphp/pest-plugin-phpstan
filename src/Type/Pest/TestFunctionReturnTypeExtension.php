<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPUnit\Framework\TestCase;

final class TestFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    public function __construct(
        private readonly PestTestCaseType $pestTestCaseType,
    ) {}

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'test';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): ?Type {
        if ($functionCall->getArgs() !== []) {
            return null;
        }

        if (! $this->pestTestCaseType->resolveIfBound($scope->getFile()) instanceof Type) {
            return null;
        }

        if (! $scope->hasVariableType('this')->yes()) {
            return null;
        }

        // @note: a bare test() proxies the running test case, so it only gets the test case type where $this is the test case; helper functions and unbound files keep the declared type.
        $thisType = $scope->getVariableType('this');

        if (! new ObjectType(TestCase::class)->isSuperTypeOf($thisType)->yes()) {
            return null;
        }

        return $thisType;
    }
}
