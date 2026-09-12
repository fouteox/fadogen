<?php

declare(strict_types=1);

use App\Enums\TemplateStatusEnum;
use App\Jobs\ProcessTemplateJob;
use App\Models\Template;
use App\Services\TemplateCommandGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToWriteFile;

beforeEach(function () {
    Queue::fake();
});

it('publishes a downloadable commands archive before marking generation completed', function () {
    $disk = Storage::fake('generated-templates');
    $template = Template::factory()->create();

    (new ProcessTemplateJob($template))->handle(new TemplateCommandGenerator);

    expect($template->refresh()->status)->toBe(TemplateStatusEnum::Completed);
    $disk->assertExists($template->id.'.tar');
    $archive = new PharData($disk->path($template->id.'.tar'));
    expect($archive->count())->toBe(1)
        ->and($archive['commands.sh']->getContent())->toContain('ddev config', 'ddev restart && ddev launch');
});

it('marks failed writes as failed and cleans its temporary workspace', function (bool $throws) {
    $template = Template::factory()->create();
    $filesystem = File::getFacadeRoot();
    $directories = [];
    File::partialMock()->shouldReceive('makeDirectory')->andReturnUsing(function (string $path, int $mode, bool $recursive) use ($filesystem, &$directories): bool {
        $directories[] = $path;

        return $filesystem->makeDirectory($path, $mode, $recursive);
    });
    $disk = Mockery::mock(Illuminate\Contracts\Filesystem\Filesystem::class);
    $write = $disk->shouldReceive('put')->once();
    if ($throws) {
        $write->andThrow(UnableToWriteFile::atLocation($template->id.'.tar'));
    } else {
        $write->andReturnFalse();
    }
    Storage::shouldReceive('disk')->with('generated-templates')->andReturn($disk);

    expect(fn () => (new ProcessTemplateJob($template))->handle(new TemplateCommandGenerator))
        ->toThrow(UnableToWriteFile::class);
    expect($template->refresh()->status)->toBe(TemplateStatusEnum::Failed)
        ->and($directories)->not->toBeEmpty();
    foreach ($directories as $directory) {
        expect(is_dir($directory))->toBeFalse();
    }
})->with(['exception' => true, 'false return' => false]);
