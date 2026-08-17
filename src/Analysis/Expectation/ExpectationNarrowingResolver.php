<?php

declare(strict_types=1);

namespace Pest\PHPStan\Analysis\Expectation;

use Pest\Expectation;
use Pest\Mixins\Expectation as MixinsExpectation;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class ExpectationNarrowingResolver
{
    private const string REBIND_METHOD = 'and';

    private const string REBIND_PARAMETER = 'value';

    private const string NEGATE_METHOD = 'not';

    private const array PASSTHROUGH_METHODS = ['when', 'unless', 'sequence', 'match', 'ray'];

    /** @var array<string, bool> Matcher name => uses loose (==) comparison */
    private const array COMPARISON_METHODS = ['toBe' => false, 'toEqual' => true];

    private const string COMPARISON_PARAMETER = 'expected';

    public function __construct(
        private readonly ExpectationMatcherRegistry $matcherRegistry,
    ) {}

    /**
     * @return list<ExpectationNarrowing>
     */
    public function resolve(MethodCall $methodCall, Scope $scope): array
    {
        $links = [];
        $current = $methodCall;

        while (($current instanceof MethodCall || $current instanceof PropertyFetch) && $current->name instanceof Identifier) {
            $links[] = $current;
            $current = $current->var;
        }

        $subject = $this->resolveSubject($current, $scope);
        if (! $subject instanceof Expr) {
            return [];
        }

        $narrowings = [];
        $negated = false;

        foreach (array_reverse($links) as $link) {
            if ($link instanceof PropertyFetch) {
                /** @var Identifier $name */
                $name = $link->name;
                if ($name->name === self::NEGATE_METHOD) {
                    $negated = ! $negated;

                    continue;
                }

                return $narrowings;
            }

            if ($link->isFirstClassCallable()) {
                return $narrowings;
            }

            /** @var Identifier $name */
            $name = $link->name;
            $methodName = $name->name;

            if ($methodName === self::NEGATE_METHOD && $link->getArgs() === []) {
                $negated = ! $negated;

                continue;
            }

            if ($methodName === self::REBIND_METHOD) {
                $rebound = MatcherArgument::first($link, self::REBIND_PARAMETER);
                if (! $rebound instanceof Expr) {
                    return $narrowings;
                }

                if ($this->mayBeExpectation($rebound, $scope)) {
                    return $narrowings;
                }

                $subject = $rebound;
                $negated = false;

                continue;
            }

            if (in_array($methodName, self::PASSTHROUGH_METHODS, true)) {
                continue;
            }

            if (! method_exists(MixinsExpectation::class, $methodName)) {
                return $narrowings;
            }

            if (isset(self::COMPARISON_METHODS[$methodName])) {
                $compared = MatcherArgument::first($link, self::COMPARISON_PARAMETER);
                if ($compared instanceof Expr) {
                    $narrowings[] = ExpectationNarrowing::comparison($subject, $compared, self::COMPARISON_METHODS[$methodName], $negated);
                }

                $negated = false;

                continue;
            }

            $assertedType = $this->matcherRegistry->assertedTypeFor($methodName, $link, $scope);

            $sound = ! $negated || $this->matcherRegistry->assertsExactTypeFor($methodName, $link, $scope);

            if ($assertedType instanceof Type && $sound) {
                $narrowings[] = ExpectationNarrowing::type($subject, $assertedType, $negated);
            }

            $negated = false;
        }

        return $narrowings;
    }

    /** @return bool True when and() may unwrap the argument to an inner value we cannot track */
    private function mayBeExpectation(Expr $expr, Scope $scope): bool
    {
        return ! new ObjectType(Expectation::class)->isSuperTypeOf($scope->getType($expr))->no();
    }

    private function resolveSubject(Expr $root, Scope $scope): ?Expr
    {
        if (! $root instanceof FuncCall || ! $root->name instanceof Name) {
            return null;
        }

        if ($root->name->toLowerString() !== 'expect') {
            return null;
        }

        if (! new ObjectType(Expectation::class)->isSuperTypeOf($scope->getType($root))->yes()) {
            return null;
        }

        return $root->getArgs()[0]->value ?? null;
    }
}
