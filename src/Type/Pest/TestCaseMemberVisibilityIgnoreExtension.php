<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPUnit\Framework\TestCase;

final class TestCaseMemberVisibilityIgnoreExtension implements IgnoreErrorExtension
{
    private const array METHOD_IDENTIFIERS = ['method.protected', 'method.private'];

    private const array STATIC_METHOD_IDENTIFIERS = ['staticMethod.protected', 'staticMethod.private'];

    private const array CONSTANT_IDENTIFIERS = ['classConstant.protected', 'classConstant.private'];

    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        $identifier = $error->getIdentifier();

        $receiver = match (true) {
            $node instanceof MethodCall && in_array($identifier, self::METHOD_IDENTIFIERS, true) => $node->var,
            $node instanceof StaticCall && in_array($identifier, self::STATIC_METHOD_IDENTIFIERS, true) => $node->class,
            $node instanceof ClassConstFetch && in_array($identifier, self::CONSTANT_IDENTIFIERS, true) => $node->class,
            default => null,
        };

        if (! $receiver instanceof Variable || $receiver->name !== 'this') {
            return false;
        }

        if (! $scope->isInAnonymousFunction()) {
            return false;
        }

        if (! $scope->hasVariableType('this')->yes()) {
            return false;
        }

        $thisType = $scope->getVariableType('this');

        if (! new ObjectType(TestCase::class)->isSuperTypeOf($thisType)->yes()) {
            return false;
        }

        // @note: the closure is bound to a subclass of the test case, so its protected members are reachable.
        if (str_ends_with($identifier, '.protected')) {
            return true;
        }

        // @note: a private member is reachable only when it is declared by a trait that uses() binds, because Pest flattens those traits onto the generated class.
        $declaringClass = $this->declaringClassName($node, $thisType, $scope);

        return $declaringClass !== null
            && $thisType instanceof PestTestCaseWithTraitsType
            && in_array($declaringClass, $thisType->getTraitNames(), true);
    }

    private function declaringClassName(Expr $node, Type $thisType, Scope $scope): ?string
    {
        if (! ($node instanceof MethodCall || $node instanceof StaticCall || $node instanceof ClassConstFetch)) {
            return null;
        }

        if (! $node->name instanceof Identifier) {
            return null;
        }

        $name = $node->name->toString();

        if ($node instanceof ClassConstFetch) {
            return $thisType->hasConstant($name)->yes()
                ? $thisType->getConstant($name)->getDeclaringClass()->getName()
                : null;
        }

        return $thisType->hasMethod($name)->yes()
            ? $thisType->getMethod($name, $scope)->getDeclaringClass()->getName()
            : null;
    }
}
