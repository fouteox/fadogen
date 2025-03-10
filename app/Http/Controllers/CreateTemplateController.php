<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ProjectConfigurationData;
use App\Models\Template;
use Illuminate\Http\Response;

final class CreateTemplateController extends Controller
{
    public function __invoke(ProjectConfigurationData $request): Response
    {
        $template = Template::create(['data' => $request->toArray()]);

        return response($template->getDownloadUrl())
            ->header('Content-Type', 'text/plain');
    }
}
