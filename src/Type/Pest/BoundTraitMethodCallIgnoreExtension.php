<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;

final class BoundTraitMethodCallIgnoreExtension implements IgnoreErrorExtension
{
    public function __construct(
        private readonly PestConfigReader $pestConfigReader,
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
    {
        if ($error->getIdentifier() !== 'method.notFound') {
            return false;
        }

        if (! $node instanceof MethodCall) {
            return false;
        }

        if (! $node->var instanceof Variable || $node->var->name !== 'this') {
            return false;
        }

        if (! $node->name instanceof Identifier) {
            return false;
        }

        $methodName = $node->name->toString();

        return array_any($this->boundTraits($scope->getFile()), fn (string $trait): bool => $this->reflectionProvider->getClass($trait)->hasNativeMethod($methodName));
    }

    /**
     * @return list<string>
     */
    private function boundTraits(string $file): array
    {
        $bindings = [
            ...$this->pestConfigReader->resolveFileBindings($file),
            ...$this->pestConfigReader->resolveBindings($file),
        ];

        return array_values(array_filter(
            $bindings,
            fn (string $class): bool => $this->reflectionProvider->hasClass($class) && $this->reflectionProvider->getClass($class)->isTrait(),
        ));
    }
}
