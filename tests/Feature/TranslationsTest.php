<?php

declare(strict_types=1);

use App\Services\I18NextTranslationsLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Translation\FileLoader;

beforeEach(function () {
    $this->translationsPath = sys_get_temp_dir().'/fadogen-translations-'.bin2hex(random_bytes(8));
    File::ensureDirectoryExists($this->translationsPath.'/en');
});

afterEach(function () {
    File::deleteDirectory($this->translationsPath);
});

test('translation dictionaries preserve nesting interpolation plurals and empty groups', function () {
    File::put($this->translationsPath.'/en.json', json_encode([
        'Hello :name' => 'Hello :name',
        'apples' => 'One apple|:count apples',
        'empty' => [],
    ], JSON_THROW_ON_ERROR));
    File::put($this->translationsPath.'/en/form.php', '<?php return '.var_export([
        'label' => ['name' => 'Your name'],
        'empty' => [],
    ], true).';');

    $loader = new I18NextTranslationsLoader(new Filesystem, new FileLoader(new Filesystem, $this->translationsPath), $this->translationsPath);

    expect($loader->loadTranslations('en'))->toBe([
        'Hello {{name}}' => 'Hello {{name}}',
        'apples_one' => 'One apple',
        'apples_other' => '{{count}} apples',
        'form.label.name' => 'Your name',
    ]);
});

test('missing locales fall back to the configured language', function () {
    File::put($this->translationsPath.'/en.json', '{"hello":"Hello"}');
    $loader = new I18NextTranslationsLoader(new Filesystem, new FileLoader(new Filesystem, $this->translationsPath), $this->translationsPath);

    expect($loader->loadTranslations('missing'))->toBe(['hello' => 'Hello']);
});

test('locale endpoints return the requested catalogue', function (string $locale) {
    $this->getJson(route('i18next.fetch', $locale))
        ->assertOk()
        ->assertJson(['laravel.name_project' => trans('laravel.name_project', [], $locale)]);
})->with(['en', 'fr', 'de', 'es']);
