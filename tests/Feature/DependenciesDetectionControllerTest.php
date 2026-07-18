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
