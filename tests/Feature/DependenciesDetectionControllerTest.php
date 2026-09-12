<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

function fakeDependencyPackage(array $metadata = [], array $files = [], int $missingFileStatus = 404): void
{
    $reference = str_repeat('a', 40);
    $responses = [
        'https://repo.packagist.org/p2/vendor/package.json' => Http::response([
            'packages' => ['vendor/package' => [array_replace([
                'name' => 'vendor/package',
                'version' => '1.0.0',
                'version_normalized' => '1.0.0.0',
                'require' => ['php' => '^8.3'],
                'source' => [
                    'type' => 'git',
                    'url' => 'https://github.com/real-owner/renamed-project.git',
                    'reference' => $reference,
                ],
            ], $metadata)]],
        ]),
    ];

    foreach (['.env.example', 'package-lock.json', 'bun.lock', 'bun.lockb'] as $file) {
        $responses["https://raw.githubusercontent.com/real-owner/renamed-project/$reference/$file"] = isset($files[$file])
            ? Http::response($files[$file])
            : Http::response(status: $missingFileStatus);
    }

    Http::fake($responses);
}

it('detects only compatible supported PHP versions', function (string $constraint, ?string $expectedVersion) {
    fakeDependencyPackage(['require' => ['php' => $constraint]]);

    $response = $this->getJson(route('dependencies.detect', [
        'package' => 'vendor/package',
    ]));

    $response->assertSuccessful();

    expect($response->json('detected.php_version'))->toBe($expectedVersion);
})->with([
    'PHP 8.3' => ['^8.3', '8.5'],
    'PHP 8.4' => ['^8.4', '8.5'],
    'PHP 8.5' => ['^8.5', '8.5'],
    'highest supported union' => ['^8.3 || ^8.5', '8.5'],
    'supported and future union' => ['^8.5 || ^8.6', '8.5'],
    'older minimum with supported versions' => ['^8.2', '8.5'],
    'unsupported old version' => ['~8.2.0', null],
    'unsupported future version' => ['^8.6', null],
    'constraint without a version' => ['*', '8.5'],
    'upper bound' => ['<8.4', '8.3'],
    'range' => ['>=8.3 <8.5', '8.4'],
    'disjoint ranges' => ['>=8.3 <8.5 || >=8.6', '8.4'],
    'patch minimum needs manual selection' => ['>=8.4.2 <8.5', null],
    'patch upper bound selects earlier branch' => ['<8.4.1', '8.3'],
    'excluded patch needs manual selection' => ['>=8.5 <8.6 !=8.5.2', null],
    'unaffected branch despite patch exclusion' => ['>=8.3 !=8.3.24', '8.5'],
]);

it('rejects invalid package names before any external request', function (mixed $package) {
    Http::fake();

    $this->getJson(route('dependencies.detect', ['package' => $package]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('package');

    Http::assertNothingSent();
})->with([
    'missing' => [null],
    'empty' => [''],
    'array' => [['vendor/package']],
    'no vendor' => ['package'],
    'extra segment' => ['vendor/package/extra'],
    'path traversal' => ['vendor/../package'],
    'URL' => ['https://example.com/package'],
    'query' => ['vendor/package?x=1'],
]);

it('uses the published repository and revision to detect optional dependencies', function () {
    fakeDependencyPackage([
        'require' => ['php' => '^8.3', 'laravel/horizon' => '*', 'laravel/reverb' => '*', 'laravel/octane' => '*'],
        'require-dev' => ['pestphp/pest' => '*'],
    ], [
        '.env.example' => "# DB_CONNECTION=mysql\nDB_CONNECTION=\"pgsql\"\n",
        'bun.lock' => '{}',
    ]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertOk()
        ->assertJsonPath('detected.database', 'pgsql')
        ->assertJsonPath('detected.javascript_package_manager', 'bun')
        ->assertJsonPath('detected.testing_framework', 'pest')
        ->assertJsonPath('detected.queue_type', 'horizon')
        ->assertJsonPath('detected.queue_driver', 'redis')
        ->assertJsonPath('detected.features', ['reverb', 'octane']);

    Http::assertSentCount(5);
});

it('detects modern and legacy lock files', function (string $file, string $manager) {
    fakeDependencyPackage(files: [$file => '{}']);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertOk()
        ->assertJsonPath('detected.javascript_package_manager', $manager);
})->with([
    ['package-lock.json', 'npm'],
    ['bun.lock', 'bun'],
    ['bun.lockb', 'bun'],
]);

it('does not request arbitrary repositories from package metadata', function (array $source) {
    fakeDependencyPackage(['source' => $source]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertOk()
        ->assertJsonPath('detected.php_version', '8.5');

    Http::assertSentCount(1);
})->with([
    'other provider' => [['url' => 'https://gitlab.com/vendor/package.git', 'reference' => str_repeat('a', 40)]],
    'host suffix' => [['url' => 'https://github.com.example.com/vendor/package.git', 'reference' => str_repeat('a', 40)]],
    'credentials' => [['url' => 'https://github.com@127.0.0.1/vendor/package.git', 'reference' => str_repeat('a', 40)]],
    'unsafe revision' => [['url' => 'https://github.com/vendor/package.git', 'reference' => '../main']]],
);

it('distinguishes a missing package from an unavailable registry', function (int $upstreamStatus, int $expectedStatus) {
    Http::fake(['https://repo.packagist.org/*' => Http::response(status: $upstreamStatus)]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertStatus($expectedStatus)
        ->assertJsonStructure(['message'])
        ->assertJsonMissingPath('detected');
})->with([[404, 404], [429, 502], [500, 502]]);

it('reports connection failures instead of pretending detection succeeded', function () {
    Http::fake(['https://repo.packagist.org/*' => Http::failedConnection()]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertStatus(502)
        ->assertJsonStructure(['message'])
        ->assertJsonMissingPath('detected');
});

it('rejects malformed registry metadata', function (mixed $body) {
    Http::fake(['https://repo.packagist.org/*' => Http::response($body)]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertStatus(502)
        ->assertJsonMissingPath('detected');
})->with([
    'invalid JSON' => ['not JSON'],
    'missing packages' => [[]],
    'invalid releases' => [['packages' => ['vendor/package' => 'invalid']]],
]);

it('explains when a PHP constraint requires manual selection', function () {
    fakeDependencyPackage(['require' => ['php' => '>=8.4.2 <8.5']]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertOk()
        ->assertJsonMissingPath('detected.php_version')
        ->assertJsonCount(1, 'detected.notifications')
        ->assertJsonPath('detected.notifications.0.type', 'warning');
});

it('expands Packagist metadata and chooses the newest stable version', function () {
    Http::fake([
        'https://repo.packagist.org/p2/vendor/package.json' => Http::response([
            'minified' => 'composer/2.0',
            'packages' => ['vendor/package' => [
                ['version' => '3.0.0-beta', 'require' => ['php' => '^8.4', 'laravel/reverb' => '*']],
                ['version' => '2.0.0'],
                ['version' => '1.0.0', 'require' => ['php' => '^8.3']],
            ]],
        ]),
    ]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertOk()
        ->assertJsonPath('detected.php_version', '8.5')
        ->assertJsonPath('detected.features', ['reverb']);
});

it('detects a development-only package from its default branch', function () {
    Http::fake([
        'https://repo.packagist.org/p2/vendor/package.json' => Http::response(['packages' => ['vendor/package' => []]]),
        'https://repo.packagist.org/p2/vendor/package~dev.json' => Http::response([
            'packages' => ['vendor/package' => [
                ['version' => 'dev-next', 'require' => ['php' => '^8.6']],
                ['version' => 'dev-main', 'default-branch' => true, 'require' => ['php' => '^8.4']],
            ]],
        ]),
    ]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertOk()
        ->assertJsonPath('detected.php_version', '8.5');
});

it('reports repository failures instead of treating files as absent', function (int $status) {
    fakeDependencyPackage(missingFileStatus: $status);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertStatus(502)
        ->assertJsonMissingPath('detected');
})->with([301, 429, 500]);

it('rejects invalid PHP constraints rather than guessing a version', function () {
    fakeDependencyPackage(['require' => ['php' => 'not a constraint']]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertStatus(502)
        ->assertJsonMissingPath('detected');
});

it('honours PHP requirements from development dependencies as well', function () {
    fakeDependencyPackage([
        'require' => ['php' => '^8.3'],
        'require-dev' => ['php' => '<8.5'],
    ]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertOk()
        ->assertJsonPath('detected.php_version', '8.4');
});

it('detects Laravel native queues from their configured connection', function (string $connection, ?string $expectedDriver) {
    fakeDependencyPackage(files: ['.env.example' => "QUEUE_CONNECTION=$connection\n"]);

    $response = $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))->assertOk();

    if ($expectedDriver === null) {
        $response->assertJsonMissingPath('detected.queue_type');
    } else {
        $response->assertJsonPath('detected.queue_type', 'native')
            ->assertJsonPath('detected.queue_driver', $expectedDriver);
    }
})->with([
    ['database', 'database'],
    ['redis', 'redis'],
    ['sync', null],
]);

it('preserves Horizon detection when the repository uses Redis queues', function () {
    fakeDependencyPackage(['require' => ['laravel/horizon' => '*']], ['.env.example' => "QUEUE_CONNECTION=redis\n"]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertOk()
        ->assertJsonPath('detected.queue_type', 'horizon')
        ->assertJsonPath('detected.queue_driver', 'redis');
});
