<?php

declare(strict_types=1);

namespace Pest\PHPStan\Rules;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPUnit\Framework\TestCase;

/**
 * @implements Rule<Expr>
 */
final class BoundTestCasePrivateMemberRule implements Rule
{
    #[Override]
    public function getNodeType(): string
    {
        return Expr::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    #[Override]
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof MethodCall && ! $node instanceof StaticCall && ! $node instanceof ClassConstFetch) {
            return [];
        }

        // @note: a closure declared inside a class keeps that class as its scope, so private members stay reachable.
        if (! $scope->isInAnonymousFunction() || $scope->isInClass()) {
            return [];
        }

        if (! $node->name instanceof Identifier) {
            return [];
        }

        $receiver = $node instanceof MethodCall ? $node->var : $node->class;

        if (! $receiver instanceof Variable || $receiver->name !== 'this') {
            return [];
        }

        if (! $scope->hasVariableType('this')->yes()) {
            return [];
        }

        $thisType = $scope->getVariableType('this');

        if (! new ObjectType(TestCase::class)->isSuperTypeOf($thisType)->yes()) {
            return [];
        }

        $reflection = $this->memberReflection($node, $node->name->toString(), $thisType, $scope);

        if (! $reflection instanceof ExtendedMethodReflection && ! $reflection instanceof ClassConstantReflection) {
            return [];
        }

        if (! $reflection->isPrivate()) {
            return [];
        }

        $declaringClass = $reflection->getDeclaringClass();

        // @note: PHPStan lets the member through only when its declaring class is a bind scope class, so anything outside that set is reported by PHPStan already.
        if (! in_array($declaringClass->getName(), $thisType->getObjectClassNames(), true)) {
            return [];
        }

        $builder = match (true) {
            $node instanceof ClassConstFetch => RuleErrorBuilder::message(sprintf(
                'Access to private constant %s of class %s.',
                $reflection->getName(),
                $declaringClass->getDisplayName(),
            ))->identifier('classConstant.private'),
            $node instanceof StaticCall => RuleErrorBuilder::message(sprintf(
                'Call to private static method %s() of class %s.',
                $reflection->getName(),
                $declaringClass->getDisplayName(),
            ))->identifier('staticMethod.private'),
            default => RuleErrorBuilder::message(sprintf(
                'Call to private method %s() of class %s.',
                $reflection->getName(),
                $declaringClass->getDisplayName(),
            ))->identifier('method.private'),
        };

        return [$builder->line($node->getStartLine())->build()];
    }

    private function memberReflection(
        MethodCall|StaticCall|ClassConstFetch $node,
        string $member,
        Type $thisType,
        Scope $scope,
    ): ExtendedMethodReflection|ClassConstantReflection|null {
        if ($node instanceof ClassConstFetch) {
            return $thisType->hasConstant($member)->yes()
                ? $thisType->getConstant($member)
                : null;
        }

        return $thisType->hasMethod($member)->yes()
            ? $thisType->getMethod($member, $scope)
            : null;
    }
}
