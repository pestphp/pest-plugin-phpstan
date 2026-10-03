<?php

declare(strict_types=1);

it('reports private members of the default test case', function (): void {
    $this->runTest();
});

it('leaves protected and public members of the default test case alone', function (): void {
    $this->getActualOutputForAssertion();
    $this->getStatus();
});
