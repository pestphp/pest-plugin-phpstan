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
        private readonly PestTestFunction $testFunction,
    ) {}

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $this->testFunction->supports($functionReflection);
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): ?Type {
        if ($functionCall->getArgs() !== []) {
            return null;
        }

        $boundType = $this->pestTestCaseType->resolveIfBound($scope->getFile());

        if (! $boundType instanceof Type) {
            return null;
        }

        if ($scope->hasVariableType('this')->yes()) {
            $thisType = $scope->getVariableType('this');

            if (new ObjectType(TestCase::class)->isSuperTypeOf($thisType)->yes()) {
                return $thisType;
            }
        }

        return $boundType;
    }
}
