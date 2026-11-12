<?php

declare(strict_types=1);

use App\Enums\DatabaseEnum;
use App\Jobs\ProcessTemplateJob;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;

/**
 * Lance la génération pour une configuration sans starter kit et renvoie le
 * script de commandes placé dans l'archive.
 */
function generatedCommandsFor(DatabaseEnum $database): string
{
    $disk = Storage::fake('generated-templates');

    $template = Template::factory()->create([
        'data' => [
            'project_name' => 'demo',
            'php_version' => '8.5',
            'database' => $database->value,
            'starter_kit' => 'none',
            'testing_framework' => 'phpunit',
            'features' => [],
        ],
    ]);

    (new ProcessTemplateJob($template))->handle();

    return (string) file_get_contents('phar://'.$disk->path($template->id.'.tar').'/commands.sh');
}

test('un projet sans starter kit est créé sur la ligne Laravel 13', function () {
    expect(generatedCommandsFor(DatabaseEnum::SQLite))
        ->toContain('ddev composer create-project "laravel/laravel:^13"')
        ->not->toContain('laravel/laravel:^12');
});

test('la base choisie est demandée à DDEV dans sa version maintenue', function (DatabaseEnum $database, string $version) {
    expect($database->getLatestVersion())->toBe($version)
        ->and(generatedCommandsFor($database))->toContain('--database='.$version.' ');
})->with([
    'MariaDB' => [DatabaseEnum::Mariadb, 'mariadb:11.4'],
    'MySQL' => [DatabaseEnum::Mysql, 'mysql:8.4'],
    'PostgreSQL' => [DatabaseEnum::Postgres, 'postgres:18'],
]);

test('SQLite ne demande aucun conteneur de base à DDEV', function () {
    expect(generatedCommandsFor(DatabaseEnum::SQLite))
        ->toContain('--omit-containers=db')
        ->not->toContain('--database=');
});
