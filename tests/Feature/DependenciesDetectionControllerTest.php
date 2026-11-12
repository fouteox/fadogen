<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

it('detects only supported PHP versions', function (string $constraint, ?string $expectedVersion) {
    Http::fake([
        'https://raw.githubusercontent.com/vendor/package/main/composer.json' => Http::response([
            'require' => ['php' => $constraint],
        ]),
        '*' => Http::response(status: 404),
    ]);

    $response = $this->getJson(route('dependencies.detect', [
        'package' => 'vendor/package',
    ]));

    $response->assertSuccessful();

    expect($response->json('detected.php_version'))->toBe($expectedVersion);
})->with([
    'PHP 8.3' => ['^8.3', '8.3'],
    'PHP 8.4' => ['^8.4', '8.4'],
    'PHP 8.5' => ['^8.5', '8.5'],
    'highest supported union' => ['^8.3 || ^8.5', '8.5'],
    'supported and future union' => ['^8.5 || ^8.6', '8.5'],
    'unsupported old version' => ['^8.2', null],
    'unsupported future version' => ['^8.6', null],
    'constraint without a version' => ['*', null],
]);

it('detects Bun from either lock file format', function (string $lockFile, string $branch) {
    Http::fake([
        'https://raw.githubusercontent.com/vendor/package/main/composer.json' => Http::response(['require' => []]),
        "https://raw.githubusercontent.com/vendor/package/$branch/$lockFile" => Http::response('lock'),
        '*' => Http::response(status: 404),
    ]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertSuccessful()
        ->assertJsonPath('detected.javascript_package_manager', 'bun');
})->with([
    'text lock file' => ['bun.lock', 'main'],
    'binary lock file' => ['bun.lockb', 'main'],
    'text lock file on master' => ['bun.lock', 'master'],
]);

it('prefers npm when both lock files exist and reads the database from the master branch', function () {
    Http::fake([
        'https://raw.githubusercontent.com/vendor/package/master/composer.json' => Http::response(['require' => ['laravel/octane' => '^2.0']]),
        'https://raw.githubusercontent.com/vendor/package/master/.env.example' => Http::response("DB_CONNECTION=pgsql\n"),
        'https://raw.githubusercontent.com/vendor/package/master/package-lock.json' => Http::response('{}'),
        'https://raw.githubusercontent.com/vendor/package/master/bun.lock' => Http::response('lock'),
        '*' => Http::response(status: 404),
    ]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertSuccessful()
        ->assertJsonPath('detected.javascript_package_manager', 'npm')
        ->assertJsonPath('detected.database', 'pgsql')
        ->assertJsonPath('detected.features', ['octane']);
});

it('asks GitHub only for the known repository files', function () {
    Http::fake(['*' => Http::response(status: 404)]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertSuccessful()
        ->assertExactJson(['detected' => ['features' => []]]);

    Http::assertSentCount(10);
    Http::assertSent(fn ($request) => $request->toPsrRequest()->getUri()->getHost() === 'raw.githubusercontent.com');
});

it('survives an unreachable GitHub', function () {
    Http::fake(['*' => Http::failedConnection()]);

    $this->getJson(route('dependencies.detect', ['package' => 'vendor/package']))
        ->assertSuccessful()
        ->assertExactJson(['detected' => ['features' => []]]);
});

it('rejects a package that is not a Composer name without calling GitHub', function (mixed $package) {
    Http::fake();

    $this->getJson(route('dependencies.detect', ['package' => $package]))
        ->assertJsonValidationErrors('package');

    Http::assertNothingSent();
})->with([
    'array' => [['a']],
    'path traversal' => ['vendor/../../etc'],
    'no vendor' => ['package'],
]);

it('returns an empty answer when no package is given', function () {
    Http::fake();

    $this->getJson(route('dependencies.detect'))->assertSuccessful()->assertExactJson([]);

    Http::assertNothingSent();
});
