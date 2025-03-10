<?php

declare(strict_types=1);

namespace App\Enums;

enum JavascriptPackageManagerEnum: string
{
    case Npm = 'npm';
    case Bun = 'bun';
}
