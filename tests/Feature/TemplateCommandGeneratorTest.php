<?php

declare(strict_types=1);

use App\Data\ProjectConfigurationData;
use App\Enums\FeaturesEnum;
use App\Models\Template;
use App\Services\TemplateCommandGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

it('keeps command generation isolated between configurations', function () {
    $generator = new TemplateCommandGenerator;
    $configuration = ProjectConfigurationData::from(Template::factory()->make()->data);

    expect($generator->generate($configuration))->toBe($generator->generate($configuration));
});

it('escapes custom package arguments as shell data', function () {
    $package = 'vendor/package; printf injected; #';
    $configuration = ProjectConfigurationData::from(array_replace(Template::factory()->make()->data, [
        'starter_kit' => 'custom',
        'custom_starter_kit' => $package,
    ]));

    $commands = (new TemplateCommandGenerator)->generate($configuration);
    $createProject = array_find($commands, fn (string $command): bool => str_starts_with($command, 'ddev composer create-project '));
    $result = Process::timeout(5)->run('ddev() { printf "<%s>\\n" "$@"; }; '.$createProject);

    expect($result->successful())->toBeTrue()
        ->and($result->output())->toBe("<composer>\n<create-project>\n<$package>\n<--stability=dev>\n<--remove-vcs>\n<--prefer-dist>\n<--no-scripts>\n");
});

it('refuses unsafe project names from historical or unvalidated records', function () {
    $configuration = ProjectConfigurationData::from(array_replace(Template::factory()->make()->data, [
        'project_name' => 'project$(printf injected)',
    ]));

    expect(fn () => (new TemplateCommandGenerator)->generate($configuration))->toThrow(InvalidArgumentException::class);
});

it('continues a custom Bun installation when no npm lockfile exists', function () {
    $configuration = ProjectConfigurationData::from(array_replace(Template::factory()->make()->data, [
        'starter_kit' => 'custom',
        'custom_starter_kit' => 'vendor/package',
        'javascript_package_manager' => 'bun',
    ]));
    $commands = (new TemplateCommandGenerator)->generate($configuration);
    $cleanup = array_find($commands, fn (string $command): bool => str_starts_with($command, 'rm ') && str_contains($command, 'package-lock.json'));
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fadogen-command-test-'.Str::uuid();
    File::makeDirectory($directory, 0700, true);

    try {
        expect($cleanup)->not->toBeNull();
        $result = Process::path($directory)->timeout(5)->run($cleanup);

        expect($result->successful())->toBeTrue($result->errorOutput())
            ->and(File::files($directory))->toBe([]);
    } finally {
        File::deleteDirectory($directory);
    }
});

it('uses the requested database queue starter and optional features', function () {
    $configuration = ProjectConfigurationData::from(array_replace(Template::factory()->make()->data, [
        'project_name' => 'audit-project',
        'database' => 'pgsql',
        'starter_kit' => 'react',
        'workos' => true,
        'queue_type' => 'horizon',
        'queue_driver' => 'valkey',
        'features' => array_column(FeaturesEnum::cases(), 'value'),
        'javascript_package_manager' => 'bun',
        'initialize_git' => false,
    ]));

    $commands = (new TemplateCommandGenerator)->generate($configuration);

    expect(implode("\n", $commands))
        ->toContain('--database=postgres:17', 'DB_CONNECTION=pgsql', 'DB_PORT=5432', 'laravel/react-starter-kit:dev-workos')
        ->not->toContain('git init');
    expect($commands)->toContain(
        'ddev add-on get fouteox/ddev-bun',
        'ddev bun install',
        'ddev add-on get fouteox/ddev-valkey',
        'ddev composer require laravel/horizon',
        'ddev add-on get fouteox/ddev-laravel-scheduling',
        'ddev add-on get fouteox/ddev-laravel-octane',
        'ddev add-on get fouteox/ddev-laravel-reverb',
    );
});

it('emits valid shell syntax for the supported starter variants', function (array $overrides) {
    $configuration = ProjectConfigurationData::from(array_replace(Template::factory()->make()->data, $overrides));
    $commands = (new TemplateCommandGenerator)->generate($configuration);

    $result = Process::input(implode("\n", $commands))->timeout(5)->run(['sh', '-n']);

    expect($result->successful())->toBeTrue($result->errorOutput());
})->with([
    'plain Laravel' => [[]],
    'React WorkOS with optional features' => [[
        'starter_kit' => 'react', 'workos' => true, 'database' => 'pgsql',
        'features' => ['schedule', 'reverb', 'octane'], 'javascript_package_manager' => 'bun',
        'queue_type' => 'horizon', 'queue_driver' => 'valkey',
    ]],
    'Vue with native Redis queue' => [[
        'starter_kit' => 'vue', 'database' => 'mariadb', 'queue_type' => 'native', 'queue_driver' => 'redis',
    ]],
    'Livewire Volt with database queue' => [[
        'starter_kit' => 'livewire', 'livewire_volt' => true, 'database' => 'mysql',
        'queue_type' => 'native', 'queue_driver' => 'database',
    ]],
    'custom package' => [['starter_kit' => 'custom', 'custom_starter_kit' => 'vendor/package']],
]);
