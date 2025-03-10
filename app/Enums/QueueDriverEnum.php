<?php

declare(strict_types=1);

namespace App\Enums;

enum QueueDriverEnum: string
{
    case Database = 'database';
    case Redis = 'redis';
    case Valkey = 'valkey';
}
