<?php

declare(strict_types=1);

use App\Models\Template;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/*
 * En production, le TLS se termine avant le conteneur : Traefik transmet la
 * requête en http avec X-Forwarded-Proto. L'URL de téléchargement est signée
 * en https ; si le proxy n'est pas de confiance, Laravel voit du http et
 * rejette la signature par un 403.
 */

test('une url signée en https reste valide derrière le proxy du cluster', function (string $proxyAddress) {
    Storage::fake('generated-templates');
    $template = Template::factory()->completed()->create();
    Storage::disk('generated-templates')->put($template->id.'.tar', 'contenu de l\'archive');

    URL::forceScheme('https');
    $signedUrl = $template->getDownloadUrl();
    URL::forceScheme(null);

    expect($signedUrl)->toStartWith('https://');

    $this->withServerVariables(['REMOTE_ADDR' => $proxyAddress])
        ->withHeaders(['X-Forwarded-Proto' => 'https'])
        ->get(str_replace('https://', 'http://', $signedUrl))
        ->assertOk();
})->with([
    'pod Traefik du cluster k3s' => '10.42.3.39',
    'réseau Docker local' => '172.18.0.2',
]);
