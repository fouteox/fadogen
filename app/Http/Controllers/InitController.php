<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TemplateStatusEnum;
use App\Models\Template;
use Illuminate\Http\Response;

final class InitController extends Controller
{
    public function index(string $template = 'laravel'): Response
    {
        return response()
            ->view('scripts.init', ['template' => $template])
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function withTemplate(Template $template): Response
    {
        if ($template->status !== TemplateStatusEnum::Completed) {
            abort(404);
        }

        return response()
            ->view('scripts.init', [
                'template' => $template->data,
                'downloadUrl' => $template->getDownloadUrl(),
                'useTemplate' => true,
            ])
            ->header('Content-Type', 'text/plain')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
