<?php

declare(strict_types=1);

namespace Tests\Type;

use FilesystemIterator;
use Pest\PHPStan\Type\Pest\PestFileDiscoverer;
use PHPStan\DependencyInjection\ContainerFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

$fixtureDir = static function (string $name): string {
    $path = realpath(__DIR__.'/Fixtures/'.$name);

    if ($path === false) {
        throw new RuntimeException(sprintf('Fixture directory %s not found.', $name));
    }

    return $path;
};

$discoverer = static fn (string $dir): PestFileDiscoverer => new PestFileDiscoverer([$dir]);

test('a discovered Pest.php is recognised as a configuration file', function () use ($fixtureDir, $discoverer): void {
    $dir = $fixtureDir('pestconfig-matrix');

    expect($discoverer($dir)->isPestConfigFile($dir.'/Pest.php'))->toBeTrue();
});

test('an ordinary test file is not a configuration file', function () use ($fixtureDir, $discoverer): void {
    $dir = $fixtureDir('pestconfig-matrix');

    expect($discoverer($dir)->isPestConfigFile($dir.'/Feature/SomeTest.php'))->toBeFalse()
        ->and($discoverer($dir)->isPestConfigFile($dir.'/Unit/UnitTest.php'))->toBeFalse();
});

test('a non-existent path is not a configuration file', function () use ($fixtureDir, $discoverer): void {
    $dir = $fixtureDir('pestconfig-matrix');

    expect($discoverer($dir)->isPestConfigFile($dir.'/Nope/Missing.php'))->toBeFalse()
        ->and($discoverer($dir)->isPestConfigFile('/definitely/not/here/Pest.php'))->toBeFalse();
});

test('a file merely named Pest.php outside the scanned tree is not recognised', function () use ($fixtureDir, $discoverer): void {
    $scoped = $discoverer($fixtureDir('pesthook-scope'));

    expect($scoped->isPestConfigFile($fixtureDir('pestconfig-matrix').'/Pest.php'))->toBeFalse()
        ->and($scoped->isPestConfigFile($fixtureDir('pesthook-scope').'/Pest.php'))->toBeTrue();
});

test('config file recognition is independent of path formatting', function () use ($fixtureDir, $discoverer): void {
    $dir = $fixtureDir('pestconfig-matrix');

    expect($discoverer($dir)->isPestConfigFile($dir.'/Feature/../Pest.php'))->toBeTrue()
        ->and($discoverer($dir)->isPestConfigFile($dir.'/./Pest.php'))->toBeTrue();
});

test('discovery is memoized and returns a stable list across calls', function () use ($fixtureDir, $discoverer): void {
    $instance = $discoverer($fixtureDir('pestconfig-matrix'));

    $first = $instance->discoverPestFiles();
    $second = $instance->discoverPestFiles();

    expect($second)->toBe($first)
        ->and($first)->not->toBeEmpty()
        ->and($first)->toBe(array_values(array_unique($first)));
});

test('every discovered file is itself recognised as a configuration file', function () use ($fixtureDir, $discoverer): void {
    $instance = $discoverer($fixtureDir('pestconfig-matrix'));

    foreach ($instance->discoverPestFiles() as $pestFile) {
        expect($instance->isPestConfigFile($pestFile))->toBeTrue()
            ->and(basename($pestFile))->toBe('Pest.php');
    }
});

test('explicit configuration paths do not search analysis paths or the project root', function () use ($fixtureDir): void {
    $dir = $fixtureDir('pesthook-scope');
    $outside = $fixtureDir('pestconfig-matrix');
    $instance = new PestFileDiscoverer([$outside], dirname(__DIR__, 2), [$dir]);

    expect($instance->isPestConfigFile($dir.'/Pest.php'))->toBeTrue()
        ->and($instance->isPestConfigFile($outside.'/Pest.php'))->toBeFalse()
        ->and($instance->isPestConfigFile(__DIR__.'/../Pest.php'))->toBeTrue();
});

test('explicit configuration paths support multiple test directories', function () use ($fixtureDir): void {
    $first = $fixtureDir('pesthook-scope');
    $second = $fixtureDir('pestconfig-matrix');
    $instance = new PestFileDiscoverer([], '', [$first, $second]);

    expect($instance->isPestConfigFile($first.'/Pest.php'))->toBeTrue()
        ->and($instance->isPestConfigFile($second.'/Pest.php'))->toBeTrue();
});

test('an empty explicit configuration path list disables discovery', function () use ($fixtureDir): void {
    $dir = $fixtureDir('pestconfig-matrix');
    $instance = new PestFileDiscoverer([$dir], dirname(__DIR__, 2), []);

    expect($instance->discoverPestFiles())->toBeEmpty();
});

test('default discovery still searches the project root', function () use ($fixtureDir): void {
    $dir = $fixtureDir('pesthook-scope');
    $outside = $fixtureDir('pestconfig-matrix');
    $instance = new PestFileDiscoverer([$dir], dirname($dir));

    expect($instance->isPestConfigFile($outside.'/Pest.php'))->toBeTrue();
});

test('PHPStan passes explicit configuration paths to the discovery service', function () use ($fixtureDir): void {
    $dir = $fixtureDir('pesthook-scope');
    $outside = $fixtureDir('pestconfig-matrix');
    $project = dirname(__DIR__, 2);
    $temporaryDir = sys_get_temp_dir().'/pest-discovery-container-'.bin2hex(random_bytes(16));
    mkdir($temporaryDir, 0700);

    try {
        $container = new ContainerFactory($project)->create($temporaryDir, [
            $project.'/extension.neon',
            __DIR__.'/Fixtures/pest-discovery.neon',
        ], [$outside]);
        $instance = $container->getByType(PestFileDiscoverer::class);

        expect($instance->isPestConfigFile($dir.'/Pest.php'))->toBeTrue()
            ->and($instance->isPestConfigFile($outside.'/Pest.php'))->toBeFalse()
            ->and($instance->isPestConfigFile(__DIR__.'/../Pest.php'))->toBeTrue();
    } finally {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($temporaryDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($temporaryDir);
    }
});
