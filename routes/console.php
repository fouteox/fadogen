<?php

declare(strict_types=1);

use App\Models\Template;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', ['--model' => [Template::class]])->daily();
