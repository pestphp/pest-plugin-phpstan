<?php

declare(strict_types=1);

use Pest\PHPStan\Type\Pest\PestTestFunction;
use Pest\TestSuite;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;

test('only Pest provides the supported global test function', function (): void {
    $provider = self::getContainer()->getByType(ReflectionProvider::class);
    $testFunction = new PestTestFunction($provider);
    $pestFilename = $provider->getClass(TestSuite::class)->getFileName();

    expect($pestFilename)->not->toBeNull();

    $pest = $this->createStub(FunctionReflection::class);
    $pest->method('getName')->willReturn('test');
    $pest->method('getFileName')->willReturn(dirname($pestFilename).'/Functions.php');

    $custom = $this->createStub(FunctionReflection::class);
    $custom->method('getName')->willReturn('test');
    $custom->method('getFileName')->willReturn(__FILE__);

    expect($testFunction->supports($pest))->toBeTrue()
        ->and($testFunction->supports($custom))->toBeFalse();
});
