<?php

declare(strict_types=1);

namespace Pest\PHPStan\Analysis\Expectation;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CallableType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

final class MatcherAssertionRegistry
{
    public const string STRING = 'string';

    public const string INT = 'int';

    public const string FLOAT = 'float';

    public const string BOOL = 'bool';

    public const string TRUE = 'true';

    public const string FALSE = 'false';

    public const string NULL = 'null';

    public const string ARRAY = 'array';

    public const string LIST = 'list';

    public const string OBJECT = 'object';

    public const string CALLABLE = 'callable';

    public const string ITERABLE = 'iterable';

    public const string NUMERIC = 'numeric';

    public const string SCALAR = 'scalar';

    public const string INSTANCE_OF = 'instance_of';

    public const string RESOURCE = 'resource';

    /** @var array<string, string> */
    private const array METHOD_ASSERTIONS = [
        'toBeString' => self::STRING,
        'toBeInt' => self::INT,
        'toBeFloat' => self::FLOAT,
        'toBeBool' => self::BOOL,
        'toBeTrue' => self::TRUE,
        'toBeFalse' => self::FALSE,
        'toBeNull' => self::NULL,
        'toBeArray' => self::ARRAY,
        'toBeList' => self::LIST,
        'toBeObject' => self::OBJECT,
        'toBeCallable' => self::CALLABLE,
        'toBeIterable' => self::ITERABLE,
        'toBeNumeric' => self::NUMERIC,
        'toBeScalar' => self::SCALAR,
        'toBeInstanceOf' => self::INSTANCE_OF,
        'toBeResource' => self::RESOURCE,
    ];

    private const string INSTANCE_OF_PARAMETER = 'class';

    /** @var array<string, Type> */
    private array $staticAssertedTypeCache = [];

    public function assertionFor(string $methodName): ?string
    {
        return self::METHOD_ASSERTIONS[$methodName] ?? null;
    }

    public function assertedTypeFor(string $methodName, MethodCall $methodCall, Scope $scope): ?Type
    {
        $assertion = $this->assertionFor($methodName);
        if ($assertion === null) {
            return null;
        }

        if ($assertion !== self::INSTANCE_OF && isset($this->staticAssertedTypeCache[$assertion])) {
            return $this->staticAssertedTypeCache[$assertion];
        }

        $assertedType = match ($assertion) {
            self::STRING => new StringType,
            self::INT => new IntegerType,
            self::FLOAT => new FloatType,
            self::BOOL => new BooleanType,
            self::TRUE => new ConstantBooleanType(true),
            self::FALSE => new ConstantBooleanType(false),
            self::NULL => new NullType,
            self::OBJECT => new ObjectWithoutClassType,
            self::CALLABLE => new CallableType,
            self::RESOURCE => new ResourceType,
            self::ARRAY => new ArrayType(
                new UnionType([new IntegerType, new StringType]),
                new MixedType,
            ),
            self::LIST => TypeCombinator::intersect(
                new ArrayType(new IntegerType, new MixedType),
                new AccessoryArrayListType,
            ),
            self::ITERABLE => new IterableType(new MixedType, new MixedType),
            self::NUMERIC => new UnionType([
                new FloatType,
                new IntegerType,
                new IntersectionType([
                    new StringType,
                    new AccessoryNumericStringType,
                ]),
            ]),
            self::SCALAR => new UnionType([
                new BooleanType,
                new FloatType,
                new IntegerType,
                new StringType,
            ]),
            self::INSTANCE_OF => $this->resolveToBeInstanceOf($methodCall, $scope),
            default => null,
        };

        if (! $assertedType instanceof Type || $assertion === self::INSTANCE_OF) {
            return $assertedType;
        }

        $this->staticAssertedTypeCache[$assertion] = $assertedType;

        return $assertedType;
    }

    /** @return bool True when the asserted type mirrors the matcher exactly, so it may also be removed */
    public function assertsExactTypeFor(string $methodName, MethodCall $methodCall, Scope $scope): bool
    {
        if ($this->assertionFor($methodName) !== self::INSTANCE_OF) {
            return true;
        }

        return count($this->constantClassNames($methodCall, $scope)) === 1;
    }

    private function resolveToBeInstanceOf(MethodCall $methodCall, Scope $scope): Type
    {
        $classNames = $this->constantClassNames($methodCall, $scope);

        if ($classNames === []) {
            return new ObjectWithoutClassType;
        }

        $objectTypes = array_map(
            static fn (ConstantStringType $name): ObjectType => new ObjectType($name->getValue()),
            $classNames
        );

        return TypeCombinator::union(...$objectTypes);
    }

    /**
     * @return list<ConstantStringType>
     */
    private function constantClassNames(MethodCall $methodCall, Scope $scope): array
    {
        $class = MatcherArgument::first($methodCall, self::INSTANCE_OF_PARAMETER);

        if (! $class instanceof Expr) {
            return [];
        }

        return $scope->getType($class)->getConstantStrings();
    }
}
