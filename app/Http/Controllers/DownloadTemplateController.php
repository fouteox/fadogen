<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TemplateStatusEnum;
use App\Models\Template;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DownloadTemplateController extends Controller
{
    public function __invoke(Template $template): StreamedResponse|Response
    {
        if ($template->status === TemplateStatusEnum::Pending) {
            return response('', SymfonyResponse::HTTP_ACCEPTED)
                ->header('Retry-After', '5');
        }

        if ($template->status === TemplateStatusEnum::Failed) {
            return response('', SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! Storage::exists($template->id.'.tar')) {
            return response('', SymfonyResponse::HTTP_NOT_FOUND);
        }

        $archiveContent = Storage::get($template->id.'.tar');

        //        Storage::delete($template->id.'.tar');

        $template->status = TemplateStatusEnum::Downloaded;
        $template->save();

        return response()->streamDownload(function () use ($archiveContent) {
            echo $archiveContent;
        }, $template->id.'.tar', [
            'Content-Type' => 'application/x-tar',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
