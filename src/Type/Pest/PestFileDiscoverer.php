<?php

declare(strict_types=1);

namespace Pest\PHPStan\Type\Pest;

use FilesystemIterator;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;

final class PestFileDiscoverer
{
    private readonly Parser $parser;

    /** @var list<string>|null Memoized result of discoverPestFiles() */
    private ?array $discoveredPestFiles = null;

    /**
     * @param  string[]  $scanPaths  PHPStan's configured analysis paths
     * @param  string[]|null  $configPaths  Explicit paths for Pest.php discovery, or null to use analysis paths and the project root
     */
    public function __construct(
        private readonly array $scanPaths,
        private readonly string $rootDir = '',
        private readonly ?array $configPaths = null,
    ) {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
    }

    /**
     * @return string[]
     */
    public function discoverPestFiles(): array
    {
        if ($this->discoveredPestFiles !== null) {
            return $this->discoveredPestFiles;
        }

        $files = [];

        $scanPaths = $this->configPaths ?? $this->scanPaths;

        if ($this->configPaths === null && $this->rootDir !== '') {
            $scanPaths[] = $this->rootDir;
        }

        foreach ($scanPaths as $scanPath) {
            $realPath = realpath($scanPath);
            if ($realPath === false) {
                continue;
            }

            $dir = is_file($realPath) ? dirname($realPath) : $realPath;
            $this->findPestFilesInDirectory($dir, $files);
            $this->findPestFilesInAncestors($dir, $files);
        }

        return $this->discoveredPestFiles = array_values(array_unique($files));
    }

    /**
     * @return array{Node[], array<string, string>}|null
     */
    public function parseFile(string $filePath): ?array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        try {
            $stmts = $this->parser->parse($content);
        } catch (Error) {
            return null;
        }

        if ($stmts === null) {
            return null;
        }

        $traverser = new NodeTraverser;
        $traverser->addVisitor(new NameResolver);

        $stmts = $traverser->traverse($stmts);
        $useMap = $this->extractUseMap($stmts);

        return [$stmts, $useMap];
    }

    public function normalizePath(string $path): string
    {
        $realPath = realpath($path);
        if ($realPath !== false) {
            return str_replace('\\', '/', $realPath);
        }

        return str_replace('\\', '/', $path);
    }

    public function isPestConfigFile(string $filePath): bool
    {
        $normalizedFile = $this->normalizePath($filePath);

        return array_any($this->discoverPestFiles(), fn (string $pestFile): bool => $this->normalizePath($pestFile) === $normalizedFile);
    }

    public function isMethodNamed(MethodCall $methodCall, string $name): bool
    {
        return $methodCall->name instanceof Identifier && $methodCall->name->toString() === $name;
    }

    /**
     * @param  string[]  $results
     */
    private function findPestFilesInDirectory(string $directory, array &$results): void
    {
        try {
            $directoryIterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
            $filterIterator = new RecursiveCallbackFilterIterator(
                $directoryIterator,
                static fn (SplFileInfo $current, string $key, RecursiveDirectoryIterator $iterator): bool => ! $current->isDir() || ! in_array($current->getFilename(), ['vendor', 'node_modules', '.git'], true),
            );
            $iterator = new RecursiveIteratorIterator($filterIterator, RecursiveIteratorIterator::LEAVES_ONLY, RecursiveIteratorIterator::CATCH_GET_CHILD);
        } catch (UnexpectedValueException) {
            return;
        }

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getFilename() !== 'Pest.php') {
                continue;
            }

            if (! $file->isFile()) {
                continue;
            }

            $realPath = realpath($file->getPathname());
            if ($realPath !== false) {
                $results[] = $realPath;
            }
        }
    }

    /**
     * @param  string[]  $results
     */
    private function findPestFilesInAncestors(string $directory, array &$results): void
    {
        $current = $directory;

        while (! $this->isProjectRoot($current)) {
            $parent = dirname($current);

            if ($parent === $current) {
                break;
            }

            $current = $parent;
            $candidate = $current.DIRECTORY_SEPARATOR.'Pest.php';

            if (is_file($candidate)) {
                $realPath = realpath($candidate);
                if ($realPath !== false) {
                    $results[] = $realPath;
                }
            }
        }
    }

    private function isProjectRoot(string $directory): bool
    {
        return is_file($directory.DIRECTORY_SEPARATOR.'composer.json')
            || is_dir($directory.DIRECTORY_SEPARATOR.'.git');
    }

    /**
     * @param  Node[]  $stmts
     * @return array<string, string>
     */
    private function extractUseMap(array $stmts): array
    {
        $useMap = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof Use_) {
                foreach ($stmt->uses as $use) {
                    $alias = $use->alias !== null ? $use->alias->name : $use->name->getLast();
                    $useMap[$alias] = $use->name->toString();
                }
            }

            if ($stmt instanceof Namespace_) {
                foreach ($stmt->stmts as $namespacedStmt) {
                    if ($namespacedStmt instanceof Use_) {
                        foreach ($namespacedStmt->uses as $use) {
                            $alias = $use->alias !== null ? $use->alias->name : $use->name->getLast();
                            $useMap[$alias] = $use->name->toString();
                        }
                    }
                }
            }
        }

        return $useMap;
    }
}
