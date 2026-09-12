<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetLocaleMiddleware
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        if (! in_array($locale, ['fr', 'en', 'de', 'es'], true)) {
            $locale = $request->getPreferredLanguage(['fr', 'en', 'de', 'es']);
        }

        if (! $locale) {
            $locale = config()->string('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
