<?php

declare(strict_types=1);

use Pest\Expectation;
use Pest\PHPStan\Analysis\Expectation\ExpectationChainStateResolver;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

beforeEach(function (): void {
    PHPStanTestCase::getContainer();
});

test('it resolves each node from its own scope', function (): void {
    $resolver = new ExpectationChainStateResolver;
    $intCall = expectationEachCall();
    $stringCall = expectationEachCall();
    $intState = $resolver->resolve($intCall, scopeReturningExpectationOf(new IntegerType, fn (string $class): Scope => $this->createStub($class)));
    $stringState = $resolver->resolve($stringCall, scopeReturningExpectationOf(new StringType, fn (string $class): Scope => $this->createStub($class)));
    expect($intState)->not->toBeNull()
        ->and($stringState)->not->toBeNull()
        ->and($intState->originalValueType->describe(VerbosityLevel::typeOnly()))->toBe('int')
        ->and($stringState->originalValueType->describe(VerbosityLevel::typeOnly()))->toBe('string');
});

test('it evicts cache entries when their node is freed', function (): void {
    $resolver = new ExpectationChainStateResolver;
    $cache = cacheOf($resolver);
    expect($cache)->toBeEmpty();
    $call = expectationEachCall();
    $resolver->resolve($call, scopeReturningExpectationOf(new IntegerType, fn (string $class): Scope => $this->createStub($class)));
    expect($cache)->toHaveCount(1, 'The resolved node should be memoised.');
    unset($call);
    expect($cache)->toHaveCount(
        0,
        'A freed node must not leave a dangling cache entry keyed by a recyclable id.',
    );
});

function expectationEachCall(): MethodCall
{
    return new MethodCall(new Variable('expectation'), new Identifier('each'));
}

function scopeReturningExpectationOf(Type $valueType, callable $stubFactory): Scope
{
    $expectationType = new GenericObjectType(Expectation::class, [$valueType]);
    $scope = $stubFactory(Scope::class);
    $scope->method('getType')->willReturn($expectationType);

    return $scope;
}

/**
 * @return WeakMap<MethodCall, mixed>
 */
function cacheOf(ExpectationChainStateResolver $resolver): WeakMap
{
    $property = new ReflectionProperty($resolver, 'stateCache');
    $cache = $property->getValue($resolver);
    expect($cache)->toBeInstanceOf(WeakMap::class);

    return $cache;
}
