<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;

final class PestHookPropertyReader
{
    private const array HOOK_FUNCTIONS = ['beforeEach'];

    /** @var array<string, array<string, list<Expr>>> Maps normalized file paths to property name → list of RHS Exprs */
    private array $filePropertyCache = [];

    /** @var array<string, array<string, list<Expr>>> Maps normalized binding keys (directories or files) to property name → list of RHS Exprs */
    private array $directoryPropertyMap = [];

    private readonly PestTargetResolver $targetResolver;

    private bool $pestFilesParsed = false;

    public function __construct(
        private readonly PestFileDiscoverer $fileDiscoverer,
    ) {
        $this->targetResolver = new PestTargetResolver($fileDiscoverer);
    }

    /**
     * @return array<string, list<Expr>>
     */
    public function getPropertyExprs(string $filePath): array
    {
        $this->ensurePestFilesParsed();

        $normalizedFile = $this->fileDiscoverer->normalizePath($filePath);

        $this->filePropertyCache[$normalizedFile] ??= $this->parseTestFile($filePath);

        $properties = $this->filePropertyCache[$normalizedFile];

        $directoryProperties = $this->resolveDirectoryProperties($filePath);
        $this->mergePropertyExprs($properties, $directoryProperties);

        return $properties;
    }

    /**
     * @return array<string, list<Expr>>
     */
    private function resolveDirectoryProperties(string $filePath): array
    {
        $normalizedFile = $this->fileDiscoverer->normalizePath($filePath);

        $properties = [];

        foreach ($this->directoryPropertyMap as $bindingKey => $bindingProperties) {
            if (! $this->targetResolver->matches($bindingKey, $normalizedFile)) {
                continue;
            }

            $this->mergePropertyExprs($properties, $bindingProperties);
        }

        return $properties;
    }

    private function ensurePestFilesParsed(): void
    {
        if ($this->pestFilesParsed) {
            return;
        }

        $this->pestFilesParsed = true;

        $pestFiles = $this->fileDiscoverer->discoverPestFiles();

        foreach ($pestFiles as $pestFile) {
            $this->parsePestConfigFile($pestFile);
        }
    }

    private function parsePestConfigFile(string $filePath): void
    {
        $parsed = $this->fileDiscoverer->parseFile($filePath);
        if ($parsed === null) {
            return;
        }

        [$stmts, $useMap] = $parsed;

        $nodeFinder = new NodeFinder;

        $this->extractUsesBeforeEachProperties($nodeFinder, $stmts, $useMap, $filePath);
    }

    /**
     * @param  Node[]  $stmts
     * @param  array<string, string>  $useMap
     */
    private function extractUsesBeforeEachProperties(NodeFinder $nodeFinder, array $stmts, array $useMap, string $pestFilePath): void
    {
        $pestFileDir = dirname($pestFilePath);

        /** @var MethodCall[] $methodCalls */
        $methodCalls = $nodeFinder->findInstanceOf($stmts, MethodCall::class);

        foreach ($methodCalls as $methodCall) {
            if (! $this->fileDiscoverer->isMethodNamed($methodCall, 'beforeEach')) {
                continue;
            }

            if (! $this->isUsesChain($methodCall->var)) {
                continue;
            }

            $targets = $this->resolveChainTargets($methodCall, $methodCalls, $pestFileDir);
            if ($targets === []) {
                continue;
            }

            foreach ($methodCall->getArgs() as $arg) {
                if (! $arg->value instanceof Closure && ! $arg->value instanceof ArrowFunction) {
                    continue;
                }

                $assigned = $this->extractPropertyAssignments($arg->value, $useMap);

                foreach ($targets as $target) {
                    $this->directoryPropertyMap[$target] ??= [];
                    $this->mergePropertyExprs($this->directoryPropertyMap[$target], $assigned);
                }

                break;
            }
        }
    }

    /**
     * @param  MethodCall[]  $allMethodCalls
     * @return list<string>
     */
    private function resolveChainTargets(MethodCall $beforeEachCall, array $allMethodCalls, string $pestFileDir): array
    {
        $targets = [];

        foreach ($allMethodCalls as $candidate) {
            if (! $this->fileDiscoverer->isMethodNamed($candidate, 'in')) {
                continue;
            }

            if (! $this->chainContains($candidate, $beforeEachCall) && ! $this->chainContains($beforeEachCall, $candidate)) {
                continue;
            }

            array_push($targets, ...$this->targetResolver->resolveInTargets($candidate, $pestFileDir));
        }

        return array_values(array_unique($targets));
    }

    private function chainContains(MethodCall $outer, MethodCall $target): bool
    {
        $expr = $outer->var;

        while ($expr instanceof MethodCall) {
            if ($expr === $target) {
                return true;
            }

            $expr = $expr->var;
        }

        return false;
    }

    /**
     * @param  array<string, list<Expr>>  $target
     * @param  array<string, list<Expr>>  $source
     */
    private function mergePropertyExprs(array &$target, array $source): void
    {
        foreach ($source as $name => $exprs) {
            $target[$name] = isset($target[$name]) ? array_merge($target[$name], $exprs) : $exprs;
        }
    }

    private function isUsesChain(Expr $expr): bool
    {
        if ($expr instanceof FuncCall) {
            if (! $expr->name instanceof Name) {
                return false;
            }

            $name = $expr->name->toString();

            return $name === 'uses' || $name === 'pest';
        }

        if ($expr instanceof MethodCall) {
            return $this->isUsesChain($expr->var);
        }

        return false;
    }

    /**
     * @return array<string, list<Expr>>
     */
    private function parseTestFile(string $filePath): array
    {
        $parsed = $this->fileDiscoverer->parseFile($filePath);
        if ($parsed === null) {
            return [];
        }

        [$stmts, $useMap] = $parsed;

        $nodeFinder = new NodeFinder;
        $properties = [];

        /** @var FuncCall[] $funcCalls */
        $funcCalls = $nodeFinder->findInstanceOf($stmts, FuncCall::class);

        foreach ($funcCalls as $funcCall) {
            if (! $funcCall->name instanceof Name) {
                continue;
            }

            $functionName = $funcCall->name->toString();
            if (! in_array($functionName, self::HOOK_FUNCTIONS, true)) {
                continue;
            }

            foreach ($funcCall->getArgs() as $arg) {
                if (! $arg->value instanceof Closure && ! $arg->value instanceof ArrowFunction) {
                    continue;
                }

                $assigned = $this->extractPropertyAssignments($arg->value, $useMap);
                $this->mergePropertyExprs($properties, $assigned);

                break;
            }
        }

        return $properties;
    }

    /**
     * @param  array<string, string>  $useMap
     * @return array<string, list<Expr>>
     */
    private function extractPropertyAssignments(Closure|ArrowFunction $hook, array $useMap): array
    {
        $properties = [];
        $localVarMap = $this->buildLocalVarExprMap($hook, $useMap);

        foreach ($hook->getStmts() as $stmt) {
            $expr = match (true) {
                $stmt instanceof Expression => $stmt->expr,
                $stmt instanceof Return_ => $stmt->expr,
                default => null,
            };

            if (! $expr instanceof Assign) {
                continue;
            }

            $var = $expr->var;
            if (! $var instanceof PropertyFetch) {
                continue;
            }

            if (! $var->var instanceof Variable) {
                continue;
            }

            $thisVariable = $var->var;

            if (! is_string($thisVariable->name)) {
                continue;
            }

            if ($thisVariable->name !== 'this') {
                continue;
            }

            if (! $var->name instanceof Identifier) {
                continue;
            }

            $propName = $var->name->name;
            $rhsExpr = $expr->expr;

            if ($rhsExpr instanceof Variable && is_string($rhsExpr->name)) {
                $varName = $rhsExpr->name;
                if (isset($localVarMap[$varName])) {
                    $rhsExpr = $localVarMap[$varName];
                }
            }

            $docComment = $stmt->getDocComment();
            if ($docComment instanceof Doc) {
                $syntheticExpr = $this->resolveExprFromDocComment($docComment->getText(), null, $useMap);
                if ($syntheticExpr instanceof Expr) {
                    $rhsExpr = $syntheticExpr;
                }
            }

            $properties[$propName][] = $rhsExpr;
        }

        return $properties;
    }

    /**
     * @param  array<string, string>  $useMap
     * @return array<string, Expr>
     */
    private function buildLocalVarExprMap(Closure|ArrowFunction $hook, array $useMap): array
    {
        $map = [];

        foreach ($hook->getStmts() as $stmt) {
            // @note: arrow functions yield one synthetic Return_ statement — no local vars can precede it.
            if (! $stmt instanceof Expression) {
                continue;
            }

            $expr = $stmt->expr;
            if (! $expr instanceof Assign) {
                continue;
            }

            $var = $expr->var;
            if (! $var instanceof Variable) {
                continue;
            }

            if (! is_string($var->name)) {
                continue;
            }

            $varName = $var->name;
            $docComment = $stmt->getDocComment();

            if ($docComment instanceof Doc) {
                $syntheticExpr = $this->resolveExprFromDocComment($docComment->getText(), $varName, $useMap);
                if ($syntheticExpr instanceof Expr) {
                    $map[$varName] = $syntheticExpr;

                    continue;
                }
            }

            $map[$varName] = $expr->expr;
        }

        return $map;
    }

    /**
     * Parses a @var doc comment and returns a synthetic Expr for the type, if resolvable.
     *
     * @param  array<string, string>  $useMap
     */
    private function resolveExprFromDocComment(string $docText, ?string $varName, array $useMap): ?Expr
    {
        if (! (bool) preg_match('/@var\s+(\S+)(?:\s+\$(\w+))?/', $docText, $matches)) {
            return null;
        }

        $typeName = $matches[1];
        $docVarName = $matches[2] ?? null;

        if (preg_match('/^\\\\?\w+(?:\\\\\w+)*$/', $typeName) !== 1) {
            return null;
        }

        if ($docVarName === 'this') {
            return null;
        }

        if ($varName !== null && $docVarName !== null && $docVarName !== $varName) {
            return null;
        }

        return $this->buildSyntheticExprFromTypeName($typeName, $useMap);
    }

    /**
     * @param  array<string, string>  $useMap
     */
    private function buildSyntheticExprFromTypeName(string $typeName, array $useMap): ?Expr
    {
        $fqcn = $useMap[$typeName] ?? $typeName;

        $builtIns = ['string', 'int', 'float', 'bool', 'null', 'array', 'object', 'mixed', 'void', 'never'];
        if (in_array(mb_strtolower($fqcn), $builtIns, true)) {
            return null;
        }

        return new New_(new FullyQualified($fqcn));
    }
}
