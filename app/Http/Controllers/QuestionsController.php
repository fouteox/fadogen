<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ProjectConfigurationData;
use App\Enums\PhpVersionEnum;
use Illuminate\Http\Response;

final class QuestionsController extends Controller
{
    public function __invoke(string $template = 'laravel'): Response
    {
        abort_unless($template === 'laravel', 404);
        $phpVersionOptions = [];

        foreach (array_reverse(PhpVersionEnum::cases()) as $index => $version) {
            $phpVersionOptions[$version->value] = 'PHP '.$version->value.($index === 0 ? ' ('.__('Recommended').')' : '');
        }

        return response()->view('prompts.laravel', [
            'phpVersionOptions' => $phpVersionOptions,
            'projectNamePattern' => ProjectConfigurationData::PROJECT_NAME_PATTERN,
        ])->header('Content-Type', 'text/x-php');
    }
}
