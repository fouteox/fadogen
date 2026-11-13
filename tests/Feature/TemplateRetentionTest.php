<?php

declare(strict_types=1);

use App\Models\Template;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

test('la purge supprime les modèles expirés avec leur archive et conserve les récents', function () {
    Event::fake();
    $disk = Storage::fake('generated-templates');

    $expired = Template::factory()->completed()->create([
        'created_at' => now()->subDays(Template::RETENTION_DAYS)->subMinute(),
    ]);
    $recent = Template::factory()->completed()->create([
        'created_at' => now()->subDays(Template::RETENTION_DAYS)->addMinute(),
    ]);

    $disk->put($expired->id.'.tar', 'ancienne archive');
    $disk->put($recent->id.'.tar', 'archive récente');

    $this->artisan('model:prune')->assertSuccessful();

    expect(Template::query()->pluck('id')->all())->toBe([$recent->id])
        ->and($disk->allFiles())->toBe([$recent->id.'.tar']);
});

test('la purge garde la ligne quand l\'archive ne peut pas être supprimée', function () {
    Event::fake();
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('delete')->andReturnFalse();
    Storage::shouldReceive('disk')->with('generated-templates')->andReturn($disk);

    $expired = Template::factory()->completed()->create([
        'created_at' => now()->subDays(Template::RETENTION_DAYS)->subMinute(),
    ]);

    $this->artisan('model:prune');

    expect(Template::query()->whereKey($expired->id)->exists())->toBeTrue();
});

test('la purge et les relevés Horizon sont planifiés', function () {
    $commands = collect(app(Schedule::class)->events())
        ->mapWithKeys(fn ($event) => [str($event->command)->after('artisan')->trim("' ")->value() => $event->expression]);

    expect($commands->all())->toMatchArray([
        'model:prune' => '0 0 * * *',
        'horizon:snapshot' => '*/5 * * * *',
    ]);
});

test('la création de modèles par l\'API est limitée en débit', function () {
    Event::fake();

    $payload = [
        'project_name' => 'demo',
        'php_version' => '8.5',
        'database' => 'sqlite',
        'starter_kit' => 'none',
        'testing_framework' => 'pest',
        'features' => [],
        'javascript_package_manager' => 'npm',
        'initialize_git' => false,
    ];

    foreach (range(1, 20) as $attempt) {
        $this->postJson(route('api.templates.store'), $payload)->assertOk();
    }

    $this->postJson(route('api.templates.store'), $payload)->assertTooManyRequests();

    expect(Template::count())->toBe(20);
});

test('les pages web ne consomment pas le quota de création', function () {
    Event::fake();

    foreach (range(1, 20) as $attempt) {
        $this->get(route('generator.index'));
    }

    $this->postJson(route('api.templates.store'), [
        'project_name' => 'demo',
        'php_version' => '8.5',
        'database' => 'sqlite',
        'starter_kit' => 'none',
        'testing_framework' => 'pest',
        'features' => [],
        'javascript_package_manager' => 'npm',
        'initialize_git' => false,
    ])->assertOk();
});
