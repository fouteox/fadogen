<?php

declare(strict_types=1);

use App\Enums\TemplateStatusEnum;
use App\Jobs\ProcessTemplateJob;
use App\Models\Template;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Configuration minimale acceptée par l'API, surchargée champ par champ.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function templatePayload(array $overrides = []): array
{
    return [
        'project_name' => 'demo',
        'php_version' => '8.5',
        'database' => 'sqlite',
        'starter_kit' => 'none',
        'testing_framework' => 'pest',
        'features' => [],
        'javascript_package_manager' => 'npm',
        'initialize_git' => false,
        ...$overrides,
    ];
}

beforeEach(function () {
    Event::fake();
    Http::preventStrayRequests();
});

test('un nom de projet portant du shell est refusé', function (string $projectName) {
    $this->postJson(route('api.templates.store'), templatePayload(['project_name' => $projectName]))
        ->assertJsonValidationErrors('project_name');

    expect(Template::count())->toBe(0);
})->with([
    'substitution de commande' => ['x$(id)'],
    'accents graves' => ['x`id`'],
    'enchaînement' => ['x; id'],
    'guillemet fermant' => ['x" && id #'],
    'espace' => ['mon projet'],
]);

test('un nom de projet usuel est accepté', function (string $projectName) {
    $this->postJson(route('api.templates.store'), templatePayload(['project_name' => $projectName]))
        ->assertOk();
})->with(['demo', 'Mon_Projet-2']);

test('un starter kit personnalisé portant du shell est refusé avant tout appel à Packagist', function (string $starterKit) {
    $this->postJson(route('api.templates.store'), templatePayload([
        'starter_kit' => 'custom',
        'custom_starter_kit' => $starterKit,
    ]))->assertJsonValidationErrors('custom_starter_kit');

    Http::assertNothingSent();
})->with([
    'enchaînement' => ['laravel/laravel; id'],
    'substitution de commande' => ['laravel/$(id)'],
    'option injectée' => ['laravel/laravel --repository=https://example.test'],
]);

test('un starter kit personnalisé au format Composer est accepté', function () {
    Http::fake(['packagist.org/*' => Http::response([])]);

    $this->postJson(route('api.templates.store'), templatePayload([
        'starter_kit' => 'custom',
        'custom_starter_kit' => 'laravel/react-starter-kit',
    ]))->assertOk();
});

test('une configuration non conforme déjà enregistrée ne produit ni script ni archive', function () {
    $disk = Storage::fake('generated-templates');

    $template = Template::factory()->create([
        'data' => templatePayload(['project_name' => 'x$(id)']),
    ]);

    $this->get(route('init.template', $template))->assertNotFound();

    expect(fn () => (new ProcessTemplateJob($template))->handle())
        ->toThrow(InvalidArgumentException::class)
        ->and($template->refresh()->status)->toBe(TemplateStatusEnum::Failed)
        ->and($disk->allFiles())->toBeEmpty();
});
