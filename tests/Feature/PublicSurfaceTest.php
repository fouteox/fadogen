<?php

declare(strict_types=1);

test('les questions ne sont servies que pour un modèle connu', function () {
    $this->get('/prompts/laravel')->assertOk();
    $this->get('/prompts/inconnu')->assertNotFound();
    $this->get('/fr/prompts/inconnu')->assertNotFound();
});

test('les routes héritées du kit d\'authentification n\'existent plus', function (string $method, string $uri) {
    $this->json($method, $uri)->assertNotFound();
})->with([
    ['GET', '/profile'],
    ['PATCH', '/profile'],
    ['DELETE', '/profile'],
    ['GET', '/api/user'],
    ['GET', '/sanctum/csrf-cookie'],
]);

test('Octane démarre FrankenPHP quand aucun serveur n\'est précisé', function () {
    $key = 'OCTANE_SERVER';
    $previous = ['env' => $_ENV[$key] ?? null, 'server' => $_SERVER[$key] ?? null];
    unset($_ENV[$key], $_SERVER[$key]);

    try {
        expect((require config_path('octane.php'))['server'])->toBe('frankenphp');
    } finally {
        if ($previous['env'] !== null) {
            $_ENV[$key] = $previous['env'];
        }

        if ($previous['server'] !== null) {
            $_SERVER[$key] = $previous['server'];
        }
    }
});
