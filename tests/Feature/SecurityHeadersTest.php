<?php

declare(strict_types=1);

test('toute réponse porte les en-têtes de sécurité', function (string $uri) {
    $this->get($uri)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
})->with([
    'page Inertia' => ['/'],
    'script d\'installation' => ['/init'],
    'catalogue de traductions' => ['/locales/fr/translation.json'],
    'sonde de santé' => ['/up'],
    'page introuvable' => ['/introuvable'],
]);

test('HSTS n\'est annoncé que sur une requête HTTPS', function () {
    $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');

    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

test('HSTS est annoncé derrière le proxy qui termine le TLS', function () {
    $this->get('http://localhost/', ['X-Forwarded-Proto' => 'https'])
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});
