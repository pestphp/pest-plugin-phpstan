<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use Pest\TestSuite;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;

final readonly class PestTestFunction
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {}

    public function supports(FunctionReflection $function): bool
    {
        return $this->isPestFunction($function, 'test');
    }

    public function supportsTodoHelper(Scope $scope): bool
    {
        $name = new FullyQualified('todo');

        return $this->reflectionProvider->hasFunction($name, $scope)
            && $this->isPestFunction($this->reflectionProvider->getFunction($name, $scope), 'todo');
    }

    public function isBareCall(Expr $expression, Scope $scope): bool
    {
        return $expression instanceof FuncCall
            && $expression->name instanceof Name
            && $expression->getArgs() === []
            && $this->reflectionProvider->hasFunction($expression->name, $scope)
            && $this->supports($this->reflectionProvider->getFunction($expression->name, $scope));
    }

    private function isPestFunction(FunctionReflection $function, string $name): bool
    {
        if ($function->getName() !== $name) {
            return false;
        }

        $filename = $function->getFileName();
        $testSuiteFilename = $this->reflectionProvider->getClass(TestSuite::class)->getFileName();

        return $filename !== null
            && $testSuiteFilename !== null
            && str_replace('\\', '/', $filename) === str_replace('\\', '/', dirname($testSuiteFilename).'/Functions.php');
    }
}
