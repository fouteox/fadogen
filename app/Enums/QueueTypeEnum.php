<?php

declare(strict_types=1);

namespace App\Enums;

enum QueueTypeEnum: string
{
    case Horizon = 'horizon';
    case Native = 'native';
}
