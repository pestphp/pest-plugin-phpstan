<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use Pest\PHPStan\Analysis\Expectation\ExpectationChainSubjectResolver;
use Pest\PHPStan\Analysis\Expectation\ExpectationMatcherRegistry;
use Pest\PHPStan\Analysis\Expectation\ExpectationTypeNarrower;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Expression as ExpressionStmt;
use PhpParser\NodeFinder;
use PhpParser\PrettyPrinter\Standard;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class ExpectationChainSubjectNarrowingExtension implements ExpressionTypeResolverExtension
{
    private readonly Standard $printer;

    /** @var array<string, list<array{0: string, 1: MethodCall, 2: int, 3: int}>> file path => list of [printed subject, toBeInstanceOf() call, enclosing statement start pos, end pos] */
    private array $chainFactsCache = [];

    /** @var array<int, true> Node ids whose declared type is being resolved; getType() declines for them so the scope resolves the subject on its own */
    private array $resolvingDeclaredTypes = [];

    public function __construct(
        private readonly PestFileDiscoverer $fileDiscoverer,
        private readonly ExpectationMatcherRegistry $matcherRegistry,
        private readonly ExpectationChainSubjectResolver $subjectResolver,
        private readonly ExpectationTypeNarrower $typeNarrower,
    ) {
        $this->printer = new Standard;
    }

    public function getType(Expr $expr, Scope $scope): ?Type
    {
        if (! $expr instanceof Variable && ! $expr instanceof ArrayDimFetch && ! $expr instanceof PropertyFetch) {
            return null;
        }

        if (isset($this->resolvingDeclaredTypes[spl_object_id($expr)])) {
            return null;
        }

        $exprStart = $expr->getStartFilePos();
        if ($exprStart < 0) {
            return null;
        }

        $facts = $this->chainFactsFor($scope->getFile());
        if ($facts === []) {
            return null;
        }

        $printedExpr = $this->printer->prettyPrintExpr($expr);

        $narrowedType = null;

        foreach ($facts as [$subjectPrint, $toBeInstanceOfCall, $stmtStart, $stmtEnd]) {
            if ($subjectPrint !== $printedExpr) {
                continue;
            }

            if ($exprStart < $stmtStart) {
                continue;
            }

            if ($exprStart > $stmtEnd) {
                continue;
            }

            if ($toBeInstanceOfCall->getEndFilePos() >= $exprStart) {
                continue;
            }

            $assertedType = $this->matcherRegistry->assertedTypeFor('toBeInstanceOf', $toBeInstanceOfCall, $scope);
            if (! $assertedType instanceof Type) {
                continue;
            }

            $narrowedType = $narrowedType instanceof Type
                ? TypeCombinator::intersect($narrowedType, $assertedType)
                : $assertedType;
        }

        if (! $narrowedType instanceof Type) {
            return null;
        }

        return $this->typeNarrower->narrow($this->declaredTypeOf($expr, $scope), $narrowedType);
    }

    /** @return Type The subject's type without this extension's narrowing, so a subject that is already more specific than the asserted class keeps its own members */
    private function declaredTypeOf(Expr $expr, Scope $scope): Type
    {
        $this->resolvingDeclaredTypes[spl_object_id($expr)] = true;

        try {
            return $scope->getType($expr);
        } finally {
            unset($this->resolvingDeclaredTypes[spl_object_id($expr)]);
        }
    }

    /**
     * @return list<array{0: string, 1: MethodCall, 2: int, 3: int}>
     */
    private function chainFactsFor(string $filePath): array
    {
        if (isset($this->chainFactsCache[$filePath])) {
            return $this->chainFactsCache[$filePath];
        }

        $parsed = $this->fileDiscoverer->parseFile($filePath);
        if ($parsed === null) {
            return $this->chainFactsCache[$filePath] = [];
        }

        [$stmts] = $parsed;

        $facts = [];

        $nodeFinder = new NodeFinder;

        /** @var ExpressionStmt[] $expressionStmts */
        $expressionStmts = $nodeFinder->findInstanceOf($stmts, ExpressionStmt::class);

        foreach ($expressionStmts as $stmt) {
            $stmtStart = $stmt->getStartFilePos();
            $stmtEnd = $stmt->getEndFilePos();
            if ($stmtStart < 0) {
                continue;
            }

            if ($stmtEnd < 0) {
                continue;
            }

            $current = $stmt->expr;

            while ($current instanceof MethodCall) {
                $fact = $this->factFor($current, $stmtStart, $stmtEnd);
                if ($fact !== null) {
                    $facts[] = $fact;
                }

                $current = $current->var;
            }
        }

        return $this->chainFactsCache[$filePath] = $facts;
    }

    /**
     * @return array{0: string, 1: MethodCall, 2: int, 3: int}|null
     */
    private function factFor(MethodCall $methodCall, int $stmtStart, int $stmtEnd): ?array
    {
        if (! $methodCall->name instanceof Identifier || $methodCall->name->toString() !== 'toBeInstanceOf') {
            return null;
        }

        $subjectExpr = $this->subjectResolver->subjectIntroducedBy($methodCall->var);
        if (! $subjectExpr instanceof Expr) {
            return null;
        }

        return [$this->printer->prettyPrintExpr($subjectExpr), $methodCall, $stmtStart, $stmtEnd];
    }
}
