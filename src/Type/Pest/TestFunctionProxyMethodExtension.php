<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use Pest\PendingCalls\TestCall;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final readonly class TestFunctionProxyMethodExtension implements ExpressionTypeResolverExtension, IgnoreErrorExtension
{
    public function __construct(
        private PestTestFunction $testFunction,
        private PestTestCaseType $testCaseType,
    ) {}

    public function getType(Expr $expr, Scope $scope): ?Type
    {
        return $this->isTodoCall($expr, $scope) ? new ObjectType(TestCall::class) : null;
    }

    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        return $error->getIdentifier() === 'method.notFound'
            && $this->isTodoCall($node, $scope);
    }

    private function isTodoCall(Node $node, Scope $scope): bool
    {
        return $node instanceof MethodCall
            && $node->name instanceof Identifier
            && $node->name->toLowerString() === 'todo'
            && $this->testFunction->isBareCall($node->var, $scope)
            && $this->testCaseType->resolveIfBound($scope->getFile()) instanceof Type
            && ! $scope->getType($node->var)->hasMethod('todo')->yes()
            && $this->testFunction->supportsTodoHelper($scope);
    }
}
