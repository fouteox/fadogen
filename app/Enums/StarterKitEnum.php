<?php

declare(strict_types=1);

namespace App\Enums;

enum StarterKitEnum: string
{
    case None = 'none';
    case React = 'react';
    case Vue = 'vue';
    case Livewire = 'livewire';
}
