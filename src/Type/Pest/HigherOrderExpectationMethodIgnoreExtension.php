<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use Pest\Expectation;
use Pest\Expectations\HigherOrderExpectation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;

final class HigherOrderExpectationMethodIgnoreExtension implements IgnoreErrorExtension
{
    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        if ($error->getIdentifier() !== 'method.notFound') {
            return false;
        }

        if (! $node instanceof MethodCall) {
            return false;
        }

        if (! $node->name instanceof Identifier) {
            return false;
        }

        $methodName = $node->name->name;
        $varType = $scope->getType($node->var);

        $expectationType = new ObjectType(Expectation::class);
        if ($expectationType->isSuperTypeOf($varType)->yes()) {
            $valueType = $varType->getTemplateType(Expectation::class, 'TValue');

            return $valueType->hasMethod($methodName)->yes();
        }

        $higherOrderType = new ObjectType(HigherOrderExpectation::class);
        if ($higherOrderType->isSuperTypeOf($varType)->yes()) {
            $valueType = $varType->getTemplateType(HigherOrderExpectation::class, 'TValue');

            return $valueType->hasMethod($methodName)->yes();
        }

        return false;
    }
}
