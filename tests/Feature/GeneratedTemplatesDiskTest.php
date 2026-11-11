<?php

declare(strict_types=1);

use App\Enums\TemplateStatusEnum;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;

/**
 * @return array<string, mixed>
 */
function generatedTemplatesDiskConfig(?string $driver): array
{
    $key = 'GENERATED_TEMPLATES_DISK_DRIVER';
    $previous = ['env' => $_ENV[$key] ?? null, 'server' => $_SERVER[$key] ?? null];

    unset($_ENV[$key], $_SERVER[$key]);

    if ($driver !== null) {
        $_ENV[$key] = $driver;
        $_SERVER[$key] = $driver;
    }

    try {
        return (require config_path('filesystems.php'))['disks']['generated-templates'];
    } finally {
        unset($_ENV[$key], $_SERVER[$key]);

        if ($previous['env'] !== null) {
            $_ENV[$key] = $previous['env'];
        }

        if ($previous['server'] !== null) {
            $_SERVER[$key] = $previous['server'];
        }
    }
}

test('les archives générées restent sur le disque local par défaut', function () {
    expect(generatedTemplatesDiskConfig(null))
        ->toMatchArray([
            'driver' => 'local',
            'root' => storage_path('app/generated-templates'),
        ]);
});

test('les archives générées passent sur le bucket partagé quand le pilote s3 est demandé', function () {
    expect(generatedTemplatesDiskConfig('s3'))
        ->toMatchArray([
            'driver' => 's3',
            'root' => 'generated-templates',
            'throw' => true,
        ]);
});

test('le téléchargement sert l\'archive lue sur le disque des archives générées', function () {
    Storage::fake('generated-templates');
    $template = Template::factory()->completed()->create();
    Storage::disk('generated-templates')->put($template->id.'.tar', 'contenu de l\'archive');

    $this->get($template->getDownloadUrl())
        ->assertSuccessful()
        ->assertDownload($template->id.'.tar')
        ->assertStreamedContent('contenu de l\'archive');

    expect($template->fresh()?->status)->toBe(TemplateStatusEnum::Downloaded);
});

test('le téléchargement répond 404 quand l\'archive est absente du disque', function () {
    Storage::fake('generated-templates');
    $template = Template::factory()->completed()->create();

    $this->get($template->getDownloadUrl())->assertNotFound();
});
