<?php

declare(strict_types=1);

use App\Services\I18NextTranslationsLoader;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Groupes PHP dont au moins une clé littérale est lue par l'interface.
 *
 * @return list<string>
 */
function translationGroupsReadByTheInterface(): array
{
    $groups = array_map(
        fn (SplFileInfo $file): string => $file->getBasename('.php'),
        File::files(lang_path('en')),
    );

    $pattern = '/(?:\bt\(\s*|i18nKey=)[\'"`{]*('.implode('|', array_map(preg_quote(...), $groups)).')\./';
    $used = [];

    foreach (File::allFiles(resource_path('js')) as $source) {
        if (preg_match_all($pattern, $source->getContents(), $matches) > 0) {
            array_push($used, ...$matches[1]);
        }
    }

    return array_values(array_unique($used));
}

test('chaque groupe de traductions lu par l\'interface lui est envoyé', function () {
    expect(translationGroupsReadByTheInterface())
        ->not->toBeEmpty()
        ->each->toBeIn(I18NextTranslationsLoader::CLIENT_GROUPS);
});

test('le catalogue envoyé contient les fichiers JSON et les seuls groupes de l\'interface', function (string $locale) {
    $catalogue = app(I18NextTranslationsLoader::class)->loadTranslations($locale);
    $json = array_filter(
        json_decode(File::get(lang_path($locale.'.json')), true),
        // Les clés à variable ou à pluriel sont réécrites à la syntaxe i18next.
        fn (string $value, string $key): bool => ! str_contains($key, ':') && ! str_contains($value, '|'),
        ARRAY_FILTER_USE_BOTH,
    );

    expect(array_keys($json))->not->toBeEmpty()->each->toBeIn(array_keys($catalogue))
        ->and($catalogue)->toHaveKey('laravel.database')
        ->and(array_filter(
            array_keys($catalogue),
            fn (string $key): bool => preg_match('/^(validation|passwords|http-statuses|auth|pagination|actions)\./', $key) === 1,
        ))->toBeEmpty();
})->with(['en', 'fr', 'de', 'es']);

test('le catalogue d\'une locale n\'est calculé qu\'une fois par processus', function () {
    $loader = app(I18NextTranslationsLoader::class);
    $first = $loader->loadTranslations('fr');

    File::partialMock()->shouldNotReceive('files');

    expect($loader->loadTranslations('fr'))->toBe($first);
});

test('le catalogue servi en JSON est cacheable et ne pose aucun cookie', function () {
    $response = $this->get(route('i18next.fetch', ['locale' => 'fr']))->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('public')->toContain('max-age=3600')
        ->and($response->headers->getCookies())->toBeEmpty()
        ->and($response->json())->toHaveKey('laravel.database');

    $this->get(route('i18next.fetch', ['locale' => 'fr']), ['If-None-Match' => $response->headers->get('ETag')])
        ->assertStatus(304);
});

test('la page complète embarque le catalogue de la locale courante', function () {
    $this->withUnencryptedCookie('locale', 'fr')
        ->get(route('welcome'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'fr')
            ->has('translations.fr.translation', fn ($catalogue) => $catalogue->has('laravel.database')->etc()));
});
