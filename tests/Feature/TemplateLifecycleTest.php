<?php

declare(strict_types=1);

use App\Enums\TemplateStatusEnum;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('generated-templates');
});

test('signed downloads respect template status', function (TemplateStatusEnum $status, int $expected) {
    $template = Template::factory()->createQuietly(['status' => $status]);
    $response = $this->get($template->getDownloadUrl())->assertStatus($expected);

    if ($status === TemplateStatusEnum::Pending) {
        $response->assertHeader('Retry-After', '5');
    }
})->with([
    [TemplateStatusEnum::Pending, 202],
    [TemplateStatusEnum::Failed, 422],
    [TemplateStatusEnum::Completed, 404],
]);

test('archives stream only through valid signatures', function () {
    $template = Template::factory()->completed()->createQuietly();
    Storage::disk('generated-templates')->put($template->id.'.tar', 'archive content');

    $this->get(route('templates.download', $template))->assertForbidden();
    $this->get($template->getDownloadUrl().'&modified=1')->assertForbidden();
    $this->get($template->getDownloadUrl())
        ->assertOk()
        ->assertDownload($template->id.'.tar')
        ->assertStreamedContent('archive content');
    expect($template->fresh()->status)->toBe(TemplateStatusEnum::Downloaded);
});

test('web and API creation share their rate limit', function () {
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->postJson(route('generator.store'), [])->assertUnprocessable();
        $this->postJson(route('api.templates.store'), [])->assertUnprocessable();
    }

    $this->postJson(route('api.templates.store'), [])->assertTooManyRequests();
    $this->assertDatabaseCount('templates', 0);
});

test('pruning removes expired finished templates and their archives only', function () {
    $this->freezeTime();
    config(['app.template_retention_days' => 7]);
    $expired = Template::factory()->completed()->createQuietly(['created_at' => now()->subDays(8)]);
    $recent = Template::factory()->completed()->createQuietly();
    $pending = Template::factory()->createQuietly(['created_at' => now()->subDays(8)]);
    foreach ([$expired, $recent, $pending] as $template) {
        Storage::disk('generated-templates')->put($template->id.'.tar', 'archive');
    }

    $this->artisan('model:prune', ['--model' => [Template::class]])->assertSuccessful();

    $this->assertModelMissing($expired);
    $this->assertModelExists($recent);
    $this->assertModelExists($pending);
    Storage::disk('generated-templates')->assertMissing($expired->id.'.tar');
    Storage::disk('generated-templates')->assertExists([$recent->id.'.tar', $pending->id.'.tar']);
});

test('unfinished profile scaffolding is not exposed', function () {
    $this->get('/profile')->assertNotFound();
    $this->patchJson('/profile', [])->assertNotFound();
    $this->deleteJson('/profile')->assertNotFound();
});
