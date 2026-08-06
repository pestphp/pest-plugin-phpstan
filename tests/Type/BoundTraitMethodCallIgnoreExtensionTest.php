<?php

declare(strict_types=1);

use Pest\PHPStan\Type\Pest\BoundTraitMethodCallIgnoreExtension;
use Pest\PHPStan\Type\Pest\PestConfigReader;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use Tests\TestCase;

function boundTraitMethodCallIgnoreExtension(): BoundTraitMethodCallIgnoreExtension
{
    return new BoundTraitMethodCallIgnoreExtension(
        TestCase::getContainer()->getByType(PestConfigReader::class),
        TestCase::getContainer()->getByType(ReflectionProvider::class),
    );
}

function methodNotFoundError(string $file): Error
{
    return new Error(
        'Call to an undefined method.',
        $file,
        identifier: 'method.notFound',
    );
}

function traitOnlyFixtureFile(): string
{
    $file = realpath(__DIR__.'/../Fixtures/CustomTestCaseInference/LocalUses/local-uses-trait-only.php');

    if ($file === false) {
        throw new RuntimeException('Fixture file not found.');
    }

    return $file;
}

test('ignores a call to a method declared on a trait bound via file-level uses()', function (): void {
    $file = traitOnlyFixtureFile();
    $node = new MethodCall(new Variable('this'), new Identifier('helperMethod'));
    $scope = $this->createStub(Scope::class);
    $scope->method('getFile')->willReturn($file);

    expect(boundTraitMethodCallIgnoreExtension()->shouldIgnore(methodNotFoundError($file), $node, $scope))->toBeTrue();
});

test('does not ignore a method not declared on any bound trait', function (): void {
    $file = traitOnlyFixtureFile();
    $node = new MethodCall(new Variable('this'), new Identifier('notARealMethod'));
    $scope = $this->createStub(Scope::class);
    $scope->method('getFile')->willReturn($file);

    expect(boundTraitMethodCallIgnoreExtension()->shouldIgnore(methodNotFoundError($file), $node, $scope))->toBeFalse();
});

test('does not ignore errors other than method.notFound', function (): void {
    $file = traitOnlyFixtureFile();
    $node = new MethodCall(new Variable('this'), new Identifier('helperMethod'));
    $scope = $this->createStub(Scope::class);
    $error = new Error('Call to a protected method.', $file, identifier: 'method.protected');

    expect(boundTraitMethodCallIgnoreExtension()->shouldIgnore($error, $node, $scope))->toBeFalse();
});

test('does not ignore method calls on a variable other than $this', function (): void {
    $file = traitOnlyFixtureFile();
    $node = new MethodCall(new Variable('other'), new Identifier('helperMethod'));
    $scope = $this->createStub(Scope::class);
    $scope->method('getFile')->willReturn($file);

    expect(boundTraitMethodCallIgnoreExtension()->shouldIgnore(methodNotFoundError($file), $node, $scope))->toBeFalse();
});
