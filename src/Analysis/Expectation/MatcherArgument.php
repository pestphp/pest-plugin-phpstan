<?php

declare(strict_types=1);

namespace Pest\PHPStan\Analysis\Expectation;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;

final class MatcherArgument
{
    /**
     * @param  string  $parameterName  Declared name of the first parameter, for named arguments
     * @return Expr|null Null whenever the argument cannot be read with certainty
     */
    public static function first(MethodCall $methodCall, string $parameterName): ?Expr
    {
        if ($methodCall->isFirstClassCallable()) {
            return null;
        }

        foreach ($methodCall->getArgs() as $position => $argument) {
            if ($argument->unpack) {
                return null;
            }

            if ($argument->name instanceof Identifier) {
                if ($argument->name->name === $parameterName) {
                    return $argument->value;
                }

                continue;
            }

            if ($position === 0) {
                return $argument->value;
            }
        }

        return null;
    }
}
