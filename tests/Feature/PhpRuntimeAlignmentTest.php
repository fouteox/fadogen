<?php

declare(strict_types=1);

test('Composer resolves dependencies for the production PHP runtime', function () {
    $composer = json_decode(file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);
    preg_match('/^FROM serversideup\/php:(\d+\.\d+\.\d+)-frankenphp@sha256:[a-f0-9]{64}/m', file_get_contents(base_path('Dockerfile')), $image);

    expect($image)->toHaveCount(2)
        ->and($composer['require']['php'])->toBe('^'.$image[1])
        ->and(data_get($composer, 'config.platform.php'))->toBe($image[1]);
});
