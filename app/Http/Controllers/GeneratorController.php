<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ProjectConfigurationData;
use App\Enums\TemplateStatusEnum;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class GeneratorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('generator');
    }

    public function show(Template $template): Response
    {
        return Inertia::render('generator/show', [
            'template' => [
                'id' => $template->id,
                'status' => $template->status,
                'download_command' => $template->status === TemplateStatusEnum::Completed
                    ? 'sh -c "$(curl -fsSL '.route('init.template', ['template' => $template]).')"'
                    : null,
            ],
        ]);
    }

    public function store(ProjectConfigurationData $request): RedirectResponse
    {
        $template = Template::create(['data' => $request->toArray()]);

        return to_route('generator.show', $template);
    }
}
