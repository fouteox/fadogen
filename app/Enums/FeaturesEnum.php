<?php

declare(strict_types=1);

namespace App\Enums;

enum FeaturesEnum: string
{
    case Schedule = 'schedule';
    case Reverb = 'reverb';
    case Octane = 'octane';
}
