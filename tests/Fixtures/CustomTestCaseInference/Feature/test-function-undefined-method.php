<?php

declare(strict_types=1);

namespace TestFunctionUndefinedMethod;

function helperWithTypo(): void
{
    test()->createHelpre();
}
