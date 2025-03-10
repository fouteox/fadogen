<?php

declare(strict_types=1);

namespace App\Enums;

enum TestingFrameworkEnum: string
{
    case Pest = 'pest';
    case PHPUnit = 'phpunit';
}
