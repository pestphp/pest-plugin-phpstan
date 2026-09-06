<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeFinder;

final class PestConfigReader
{
    /** @var array<string, list<string>> Maps normalized binding keys (directories or files) to fully-qualified class/trait names */
    private array $directoryMap = [];

    /** @var array<string, list<array{class: string, config: string}>> */
    private array $globalUseDirectoryMap = [];

    /** @var list<string> Bindings declared in a Pest.php config without an ->in() scope */
    private array $globalBindings = [];

    /** @var array<string, list<string>> Caches bindings declared directly inside a test file */
    private array $fileBindingsCache = [];

    private readonly PestTargetResolver $targetResolver;

    private bool $parsed = false;

    public function __construct(
        private readonly PestFileDiscoverer $fileDiscoverer,
    ) {
        $this->targetResolver = new PestTargetResolver($fileDiscoverer);
    }

    /**
     * @return list<string>
     */
    public function resolveBindings(string $filePath): array
    {
        $this->ensureParsed();

        $normalizedFile = $this->fileDiscoverer->normalizePath($filePath);
        $bindings = [...$this->globalBindings];

        foreach ($this->directoryMap as $bindingKey => $classNames) {
            if (! $this->targetResolver->matches($bindingKey, $normalizedFile)) {
                continue;
            }

            array_push($bindings, ...$classNames);
        }

        return array_values(array_unique($bindings));
    }

    /**
     * @return list<string>
     */
    public function resolveFileBindings(string $filePath): array
    {
        $normalizedFile = $this->fileDiscoverer->normalizePath($filePath);

        if (isset($this->fileBindingsCache[$normalizedFile])) {
            return $this->fileBindingsCache[$normalizedFile];
        }

        $parsed = $this->fileDiscoverer->parseFile($filePath);
        if ($parsed === null) {
            return $this->fileBindingsCache[$normalizedFile] = [];
        }

        [$stmts] = $parsed;

        $bindings = [];
        foreach ($this->topLevelExpressions($stmts) as $expr) {
            $usesArgs = $this->extractFileUsesArgs($expr);
            if ($usesArgs !== []) {
                array_push($bindings, ...$usesArgs);

                continue;
            }

            $pestArgs = $this->extractFilePestArgs($expr);
            if ($pestArgs !== []) {
                array_push($bindings, ...$pestArgs);
            }
        }

        return $this->fileBindingsCache[$normalizedFile] = array_values(array_unique($bindings));
    }

    /**
     * @return list<array{class: string, config: string}>
     */
    public function resolveGlobalUses(string $filePath): array
    {
        $this->ensureParsed();

        $normalizedFile = $this->fileDiscoverer->normalizePath($filePath);
        $bindings = [];

        foreach ($this->globalUseDirectoryMap as $bindingKey => $directoryBindings) {
            if ($this->targetResolver->matches($bindingKey, $normalizedFile)) {
                array_push($bindings, ...$directoryBindings);
            }
        }

        return $bindings;
    }

    /**
     * @return list<string>
     */
    public function resolveChainBindings(Expr $expr): array
    {
        // @note: collects the extend()/use() bindings of a single uses()/pest() chain, ignoring any in() targets in between.
        $bindings = $this->resolveUsesClassNames($expr);

        $pestBindings = $this->resolvePestClassNames($expr);
        if ($pestBindings !== null) {
            array_push($bindings, ...$pestBindings['extend'], ...$pestBindings['use']);
        }

        return array_values(array_unique($bindings));
    }

    /**
     * @return list<string>
     */
    public function allBoundClasses(): array
    {
        $this->ensureParsed();

        $bindings = [...$this->globalBindings];

        foreach ($this->directoryMap as $classNames) {
            array_push($bindings, ...$classNames);
        }

        return array_values(array_unique($bindings));
    }

    private function ensureParsed(): void
    {
        if ($this->parsed) {
            return;
        }

        $this->parsed = true;

        $pestFiles = $this->fileDiscoverer->discoverPestFiles();

        foreach ($pestFiles as $pestFile) {
            $this->parsePestFile($pestFile);
        }
    }

    private function parsePestFile(string $filePath): void
    {
        $parsed = $this->fileDiscoverer->parseFile($filePath);
        if ($parsed === null) {
            return;
        }

        [$stmts] = $parsed;

        $nodeFinder = new NodeFinder;
        $pestFileDir = dirname($filePath);

        $this->extractUsesBindings($nodeFinder, $stmts, $pestFileDir, $filePath);
        $this->extractPestBindings($nodeFinder, $stmts, $pestFileDir, $filePath);
        $this->extractGlobalBindings($stmts);
    }

    /**
     * @param  Node[]  $stmts
     */
    private function extractGlobalBindings(array $stmts): void
    {
        foreach ($this->topLevelExpressions($stmts) as $expr) {
            $usesArgs = $this->extractFileUsesArgs($expr);
            if ($usesArgs !== []) {
                array_push($this->globalBindings, ...$usesArgs);

                continue;
            }

            $pestArgs = $this->extractFilePestArgs($expr);
            if ($pestArgs !== []) {
                array_push($this->globalBindings, ...$pestArgs);
            }
        }
    }

    /**
     * @param  Node[]  $stmts
     */
    private function extractUsesBindings(NodeFinder $nodeFinder, array $stmts, string $pestFileDir, string $pestFile): void
    {
        $methodCalls = $nodeFinder->findInstanceOf($stmts, MethodCall::class);

        foreach ($methodCalls as $methodCall) {
            if (! $this->fileDiscoverer->isMethodNamed($methodCall, 'in')) {
                continue;
            }

            $classNames = $this->resolveUsesClassNames($methodCall->var);
            if ($classNames === []) {
                continue;
            }

            $this->registerDirectoryBindings($classNames, $methodCall, $pestFileDir);
            $this->registerGlobalUseBindings($classNames, $methodCall, $pestFileDir, $pestFile);
        }
    }

    /**
     * @param  Node[]  $stmts
     */
    private function extractPestBindings(NodeFinder $nodeFinder, array $stmts, string $pestFileDir, string $pestFile): void
    {
        $methodCalls = $nodeFinder->findInstanceOf($stmts, MethodCall::class);

        foreach ($methodCalls as $methodCall) {
            if (! $this->fileDiscoverer->isMethodNamed($methodCall, 'in')) {
                continue;
            }

            $pestBindings = $this->resolvePestClassNames($methodCall->var);
            if ($pestBindings === null) {
                continue;
            }

            $classNames = [...$pestBindings['extend'], ...$pestBindings['use']];
            if ($classNames !== []) {
                $this->registerDirectoryBindings($classNames, $methodCall, $pestFileDir);
            }

            if ($pestBindings['use'] !== []) {
                $this->registerGlobalUseBindings($pestBindings['use'], $methodCall, $pestFileDir, $pestFile);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function resolveUsesClassNames(Expr $expr): array
    {
        if ($expr instanceof FuncCall && $this->isFuncNamed($expr, 'uses')) {
            return $this->extractAllClassArgs($expr);
        }

        if ($expr instanceof MethodCall) {
            return $this->resolveUsesClassNames($expr->var);
        }

        return [];
    }

    /**
     * @return array{extend: list<string>, use: list<string>}|null
     */
    private function resolvePestClassNames(Expr $expr): ?array
    {
        if ($this->isPestFuncCall($expr)) {
            return ['extend' => [], 'use' => []];
        }

        if (! $expr instanceof MethodCall) {
            return null;
        }

        $bindings = $this->resolvePestClassNames($expr->var);
        if ($bindings === null) {
            return null;
        }

        if ($this->isPestExtendMethod($expr)) {
            array_push($bindings['extend'], ...$this->extractAllClassArgs($expr));
        } elseif ($this->isPestUseMethod($expr)) {
            array_push($bindings['use'], ...$this->extractAllClassArgs($expr));
        }

        return $bindings;
    }

    private function isPestExtendMethod(MethodCall $methodCall): bool
    {
        if ($this->fileDiscoverer->isMethodNamed($methodCall, 'extend')) {
            return true;
        }

        return $this->fileDiscoverer->isMethodNamed($methodCall, 'extends');
    }

    private function isPestUseMethod(MethodCall $methodCall): bool
    {
        if ($this->fileDiscoverer->isMethodNamed($methodCall, 'use')) {
            return true;
        }

        return $this->fileDiscoverer->isMethodNamed($methodCall, 'uses');
    }

    private function isPestFuncCall(Expr $expr): bool
    {
        return $expr instanceof FuncCall && $this->isFuncNamed($expr, 'pest');
    }

    /**
     * @param  list<string>  $classNames
     */
    private function registerDirectoryBindings(array $classNames, MethodCall $inMethodCall, string $pestFileDir): void
    {
        foreach ($this->targetResolver->resolveInTargets($inMethodCall, $pestFileDir) as $bindingKey) {
            $this->appendBindings($bindingKey, $classNames);
        }
    }

    /**
     * @param  list<string>  $classNames
     */
    private function registerGlobalUseBindings(
        array $classNames,
        MethodCall $inMethodCall,
        string $pestFileDir,
        string $pestFile,
    ): void {
        foreach ($this->targetResolver->resolveInTargets($inMethodCall, $pestFileDir) as $bindingKey) {
            $this->globalUseDirectoryMap[$bindingKey] ??= [];

            foreach ($classNames as $className) {
                $this->globalUseDirectoryMap[$bindingKey][] = [
                    'class' => $className,
                    'config' => $this->fileDiscoverer->normalizePath($pestFile),
                ];
            }
        }
    }

    /**
     * @param  list<string>  $classNames
     */
    private function appendBindings(string $bindingKey, array $classNames): void
    {
        $this->directoryMap[$bindingKey] ??= [];

        array_push($this->directoryMap[$bindingKey], ...$classNames);
    }

    /**
     * @return list<string>
     */
    private function extractAllClassArgs(FuncCall|MethodCall $call): array
    {
        $classNames = [];

        foreach ($call->getArgs() as $arg) {
            $value = $arg->value;

            if ($value instanceof ClassConstFetch && $value->name instanceof Identifier && $value->name->toString() === 'class' && $value->class instanceof Name) {
                $classNames[] = $value->class->toString();

                continue;
            }

            if ($value instanceof String_) {
                $classNames[] = $value->value;
            }
        }

        return $classNames;
    }

    private function isFuncNamed(FuncCall $funcCall, string $name): bool
    {
        return $funcCall->name instanceof Name && $funcCall->name->toString() === $name;
    }

    /**
     * @param  Node[]  $stmts
     * @return list<Expr>
     */
    private function topLevelExpressions(array $stmts): array
    {
        $expressions = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof Namespace_) {
                foreach ($stmt->stmts as $namespacedStmt) {
                    if ($namespacedStmt instanceof Expression) {
                        $expressions[] = $namespacedStmt->expr;
                    }
                }

                continue;
            }

            if ($stmt instanceof Expression) {
                $expressions[] = $stmt->expr;
            }
        }

        return $expressions;
    }

    /**
     * @return list<string>
     */
    private function extractFileUsesArgs(Expr $expr): array
    {
        if ($this->chainHasIn($expr)) {
            return [];
        }

        $root = $this->rootFuncCall($expr);
        if (! $root instanceof FuncCall || ! $this->isFuncNamed($root, 'uses')) {
            return [];
        }

        return $this->extractAllClassArgs($root);
    }

    /**
     * @return list<string>
     */
    private function extractFilePestArgs(Expr $expr): array
    {
        if (! $expr instanceof MethodCall || $this->chainHasIn($expr)) {
            return [];
        }

        $pestBindings = $this->resolvePestClassNames($expr);
        if ($pestBindings === null) {
            return [];
        }

        return [...$pestBindings['extend'], ...$pestBindings['use']];
    }

    private function rootFuncCall(Expr $expr): ?FuncCall
    {
        while ($expr instanceof MethodCall) {
            $expr = $expr->var;
        }

        return $expr instanceof FuncCall ? $expr : null;
    }

    private function chainHasIn(Expr $expr): bool
    {
        while ($expr instanceof MethodCall) {
            if ($this->fileDiscoverer->isMethodNamed($expr, 'in')) {
                return true;
            }

            $expr = $expr->var;
        }

        return false;
    }
}
