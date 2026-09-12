<?php

declare(strict_types=1);

use App\Data\ProjectConfigurationData;
use App\Enums\FeaturesEnum;
use App\Enums\QueueTypeEnum;
use App\Enums\StarterKitEnum;
use App\Jobs\ProcessTemplateJob;
use App\Models\Template;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->configuration = [
        'project_name' => 'my-project',
        'php_version' => '8.5',
        'database' => 'sqlite',
        'starter_kit' => 'none',
        'custom_starter_kit' => null,
        'livewire_volt' => false,
        'workos' => false,
        'testing_framework' => 'phpunit',
        'queue_type' => null,
        'queue_driver' => null,
        'features' => [],
        'javascript_package_manager' => 'npm',
        'initialize_git' => false,
    ];
});

it('rejects project names that cannot safely identify a DDEV project', function (string $name) {
    $this->configuration['project_name'] = $name;

    expect(fn () => ProjectConfigurationData::validateAndCreate($this->configuration))
        ->toThrow(ValidationException::class);
})->with(['my project', 'my_project', '../project', '-project', 'project-', 'project$(printf audit)', "project\nother"]);

it('accepts DDEV hostname names', function (string $name) {
    $this->configuration['project_name'] = $name;

    expect(ProjectConfigurationData::validateAndCreate($this->configuration)->project_name)->toBe($name);
})->with(['project', 'my-project', 'Project42', 'project.local', '1']);

it('normalizes absent or null feature lists', function (bool $present) {
    if ($present) {
        $this->configuration['features'] = null;
    } else {
        unset($this->configuration['features']);
    }

    expect(ProjectConfigurationData::validateAndCreate($this->configuration)->features)->toBe([]);
})->with([true, false]);

it('casts feature items to enums while preserving their JSON representation', function () {
    $configuration = ProjectConfigurationData::validateAndCreate(array_replace($this->configuration, [
        'features' => ['reverb', 'octane'],
    ]));

    expect($configuration->features)->toBe([FeaturesEnum::Reverb, FeaturesEnum::Octane])
        ->and($configuration->toArray()['features'])->toBe(['reverb', 'octane']);
});

it('validates the supplied configuration independently of the ambient request', function (array $changes) {
    expect(fn () => ProjectConfigurationData::validateAndCreate(array_replace($this->configuration, $changes)))
        ->toThrow(ValidationException::class);
})->with([
    'custom package missing' => [['starter_kit' => 'custom']],
    'custom package missing with enum input' => [['starter_kit' => StarterKitEnum::Custom]],
    'horizon driver missing' => [['queue_type' => 'horizon']],
    'horizon driver missing with enum input' => [['queue_type' => QueueTypeEnum::Horizon]],
    'horizon database driver' => [['queue_type' => 'horizon', 'queue_driver' => 'database']],
    'workos without starter' => [['workos' => '1']],
    'volt without livewire' => [['livewire_volt' => 1]],
    'volt with workos' => [['starter_kit' => 'livewire', 'livewire_volt' => '1', 'workos' => '1']],
]);

it('preserves accepted boolean representations for compatible configurations', function (bool|int|string $enabled) {
    $configuration = array_replace($this->configuration, ['starter_kit' => 'react', 'workos' => $enabled]);

    expect(ProjectConfigurationData::validateAndCreate($configuration)->workos)->toBeTrue();
})->with([true, 1, '1']);

it('rejects malformed custom package names before contacting Packagist', function (mixed $package) {
    Http::fake();

    expect(fn () => ProjectConfigurationData::validateAndCreate(array_replace($this->configuration, [
        'starter_kit' => 'custom',
        'custom_starter_kit' => $package,
    ])))->toThrow(ValidationException::class);

    Http::assertNothingSent();
})->with([
    'shell separators' => ['vendor/package;printf audit'],
    'URL fragment' => ['vendor/package#fragment'],
    'path traversal' => ['../package'],
    'extra arguments' => ['vendor/package extra'],
    'array' => [['vendor/package']],
    'integer' => [123],
]);

it('verifies a well formed custom package using Packagist metadata', function () {
    Http::fake(['packagist.org/packages/vendor/package.json' => Http::response(['package' => ['name' => 'vendor/package']])]);

    $configuration = ProjectConfigurationData::validateAndCreate(array_replace($this->configuration, [
        'starter_kit' => 'custom',
        'custom_starter_kit' => 'vendor/package',
    ]));

    expect($configuration->custom_starter_kit)->toBe('vendor/package');
    Http::assertSentCount(1);
});

it('produces factory configurations that satisfy the application contract', function () {
    $configuration = Template::factory()->make()->data;

    expect(ProjectConfigurationData::validateAndCreate($configuration)->toArray())->toBe($configuration);
});

it('persists validated configuration and dispatches generation through both HTTP entry points', function (string $routeName) {
    Queue::fake();

    $response = $this->postJson(route($routeName), $this->configuration);

    if ($routeName === 'api.templates.store') {
        $response->assertOk()->assertHeader('Content-Type', 'text/plain; charset=utf-8');
    } else {
        $response->assertRedirect();
    }

    $this->assertDatabaseCount('templates', 1);
    expect(Template::sole()->data)->toBe($this->configuration);
    Queue::assertPushed(ProcessTemplateJob::class, 1);
})->with(['api.templates.store', 'generator.store']);

it('rejects unsafe HTTP configuration before storing or dispatching', function (string $routeName) {
    Queue::fake();

    $this->postJson(route($routeName), array_replace($this->configuration, [
        'project_name' => 'project$(printf injected)',
    ]))->assertUnprocessable()->assertJsonValidationErrors('project_name');

    $this->assertDatabaseCount('templates', 0);
    Queue::assertNothingPushed();
})->with(['api.templates.store', 'generator.store']);

it('supports precognitive validation without creating a template', function (bool $valid) {
    Queue::fake();
    $configuration = array_replace($this->configuration, ['project_name' => $valid ? 'safe-name' : 'unsafe name']);

    $response = $this->withHeaders([
        'Precognition' => 'true',
        'Precognition-Validate-Only' => 'project_name',
    ])->postJson(route('generator.store'), $configuration);

    if ($valid) {
        $response->assertNoContent()->assertHeader('Precognition-Success', 'true');
    } else {
        $response->assertUnprocessable()->assertJsonValidationErrors('project_name');
    }

    $this->assertDatabaseCount('templates', 0);
    Queue::assertNothingPushed();
})->with([true, false]);

it('accepts disabled optional flags and rejects enabled incompatible flags through HTTP', function (string $entryPoint, array $flags, bool $valid) {
    Queue::fake();
    $configuration = $this->configuration;
    unset($configuration['livewire_volt'], $configuration['workos']);
    $configuration = array_replace($configuration, $flags);

    if ($entryPoint === 'precognition') {
        $this->withHeaders(['Precognition' => 'true']);
    }

    $response = $this->postJson(route($entryPoint === 'api' ? 'api.templates.store' : 'generator.store'), $configuration);

    if (! $valid) {
        $response->assertUnprocessable()->assertJsonValidationErrors(['livewire_volt', 'workos']);
        $this->assertDatabaseCount('templates', 0);
        Queue::assertNothingPushed();

        return;
    }

    if ($entryPoint === 'precognition') {
        $response->assertNoContent()->assertHeader('Precognition-Success', 'true');
        $this->assertDatabaseCount('templates', 0);
        Queue::assertNothingPushed();

        return;
    }

    if ($entryPoint === 'api') {
        $response->assertOk();
    } else {
        $response->assertRedirect();
    }

    $this->assertDatabaseCount('templates', 1);
    $template = Template::sole();
    expect($template->data['livewire_volt'])->toBe(($flags['livewire_volt'] ?? null) === null ? null : false)
        ->and($template->data['workos'])->toBe(($flags['workos'] ?? null) === null ? null : false);
    Queue::assertPushed(ProcessTemplateJob::class, 1);
})->with(['api', 'web', 'precognition'])->with([
    'omitted' => [[], true],
    'null' => [['livewire_volt' => null, 'workos' => null], true],
    'false' => [['livewire_volt' => false, 'workos' => false], true],
    'integer zero' => [['livewire_volt' => 0, 'workos' => 0], true],
    'string zero' => [['livewire_volt' => '0', 'workos' => '0'], true],
    'true' => [['livewire_volt' => true, 'workos' => true], false],
    'integer one' => [['livewire_volt' => 1, 'workos' => 1], false],
    'string one' => [['livewire_volt' => '1', 'workos' => '1'], false],
]);
