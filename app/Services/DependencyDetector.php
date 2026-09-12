<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\DatabaseEnum;
use App\Enums\FeaturesEnum;
use App\Enums\JavascriptPackageManagerEnum;
use App\Enums\PhpVersionEnum;
use App\Enums\QueueDriverEnum;
use App\Enums\QueueTypeEnum;
use App\Enums\TestingFrameworkEnum;
use Composer\MetadataMinifier\MetadataMinifier;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Intervals;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Dotenv\Dotenv;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * @phpstan-type PackageRelease array{
 *     version: string,
 *     require: array<string, string>,
 *     require-dev: array<string, string>,
 *     source: mixed,
 *     time: string,
 *     default-branch: bool
 * }
 */
final class DependencyDetector
{
    /**
     * @return array{
     *     features: list<string>,
     *     php_version?: string,
     *     database?: string,
     *     javascript_package_manager?: string,
     *     queue_type?: string,
     *     queue_driver?: string,
     *     testing_framework?: string,
     *     notifications?: list<array{type: string, message: string}>
     * }
     */
    public function detect(string $package): array
    {
        $release = $this->release($package);
        $requirements = $release['require'];
        $developmentRequirements = $release['require-dev'];

        $dependencies = $requirements + $developmentRequirements;
        $detected = $this->detectDependencies($dependencies);

        $phpConstraints = array_filter([$requirements['php'] ?? null, $developmentRequirements['php'] ?? null], fn (mixed $constraint): bool => $constraint !== null);

        if ($phpConstraints !== []) {
            $phpVersion = $this->phpVersion($phpConstraints);

            if ($phpVersion !== null) {
                $detected['php_version'] = $phpVersion;
            } else {
                $detected['notifications'] = [[
                    'type' => 'warning',
                    'message' => 'La contrainte PHP de ce package ne permet pas de sélectionner automatiquement une branche compatible. Vérifiez la version PHP choisie.',
                ]];
            }
        }

        return $detected + $this->detectRepositoryFiles($release['source']);
    }

    /**
     * @return PackageRelease
     */
    private function release(string $package): array
    {
        $releases = $this->releases($package);

        if ($releases === []) {
            $releases = $this->releases($package, development: true);

            abort_if($releases === [], 404, 'Aucune version de ce package n’est disponible.');

            return collect($releases)->firstWhere('default-branch', true)
                ?? collect($releases)->sortByDesc('time')->firstOrFail();
        }

        $stableReleases = array_filter($releases, fn (array $release): bool => VersionParser::parseStability($release['version']) === 'stable');
        $releasesByVersion = array_column($stableReleases ?: $releases, null, 'version');

        return $releasesByVersion[Semver::rsort(array_keys($releasesByVersion))[0]];
    }

    /**
     * @return list<PackageRelease>
     */
    private function releases(string $package, bool $development = false): array
    {
        $suffix = $development ? '~dev' : '';
        $response = Http::acceptJson()
            ->withUserAgent('Fadogen dependency detector')
            ->connectTimeout(3)
            ->timeout(10)
            ->withoutRedirecting()
            ->get("https://repo.packagist.org/p2/$package$suffix.json");

        abort_if($response->notFound(), 404, 'Ce package est introuvable sur Packagist.');
        $response->throw();
        abort_unless($response->successful(), 502, 'Le registre de packages a retourné une réponse inattendue.');

        $body = $response->json();
        $releases = is_array($body) && is_array($body['packages'] ?? null) ? ($body['packages'][$package] ?? null) : null;

        abort_unless(is_array($releases) && array_is_list($releases), 502, 'Les métadonnées du package sont invalides.');
        abort_unless(array_all($releases, fn (mixed $release): bool => is_array($release)), 502, 'Les versions du package sont invalides.');

        $expandedReleases = ($body['minified'] ?? null) === 'composer/2.0' ? MetadataMinifier::expand($releases) : $releases;
        $normalizedReleases = [];

        foreach ($expandedReleases as $release) {
            abort_unless(is_array($release) && is_string($release['version'] ?? null), 502, 'Les versions du package sont invalides.');

            $time = $release['time'] ?? '';
            $defaultBranch = $release['default-branch'] ?? false;

            abort_unless(is_string($time) && is_bool($defaultBranch), 502, 'Les métadonnées du package sont invalides.');

            $normalizedReleases[] = [
                'version' => $release['version'],
                'require' => $this->requirements($release['require'] ?? []),
                'require-dev' => $this->requirements($release['require-dev'] ?? []),
                'source' => $release['source'] ?? null,
                'time' => $time,
                'default-branch' => $defaultBranch,
            ];
        }

        return $normalizedReleases;
    }

    /**
     * @return array<string, string>
     */
    private function requirements(mixed $requirements): array
    {
        abort_unless(is_array($requirements), 502, 'Les dépendances du package sont invalides.');

        $validated = [];

        foreach ($requirements as $package => $constraint) {
            abort_unless(is_string($package) && is_string($constraint), 502, 'Les contraintes du package sont invalides.');

            $validated[$package] = $constraint;
        }

        return $validated;
    }

    /**
     * @param  array<int, string>  $constraints
     */
    private function phpVersion(array $constraints): ?string
    {
        $parser = new VersionParser;
        $required = MultiConstraint::create(array_map($parser->parseConstraints(...), $constraints));

        try {
            foreach (array_reverse(PhpVersionEnum::cases()) as $version) {
                $branch = $parser->parseConstraints("~{$version->value}.0");

                if (Intervals::isSubsetOf($branch, $required)) {
                    return $version->value;
                }
            }
        } finally {
            Intervals::clear();
        }

        return null;
    }

    /**
     * @param  array<string, string>  $dependencies
     * @return array{features: list<string>, queue_type?: string, queue_driver?: string, testing_framework?: string}
     */
    private function detectDependencies(array $dependencies): array
    {
        $detected = ['features' => []];

        if (isset($dependencies['laravel/horizon'])) {
            $detected['queue_type'] = QueueTypeEnum::Horizon->value;
            $detected['queue_driver'] = isset($dependencies['laravel/valkey']) ? QueueDriverEnum::Valkey->value : QueueDriverEnum::Redis->value;
        }

        foreach (['laravel/reverb' => FeaturesEnum::Reverb, 'laravel/octane' => FeaturesEnum::Octane] as $package => $feature) {
            if (isset($dependencies[$package])) {
                $detected['features'][] = $feature->value;
            }
        }

        if (isset($dependencies['pestphp/pest'])) {
            $detected['testing_framework'] = TestingFrameworkEnum::Pest->value;
        } elseif (isset($dependencies['phpunit/phpunit'])) {
            $detected['testing_framework'] = TestingFrameworkEnum::PHPUnit->value;
        }

        return $detected;
    }

    /**
     * @return array{database?: string, javascript_package_manager?: string, queue_type?: string, queue_driver?: string}
     */
    private function detectRepositoryFiles(mixed $source): array
    {
        if (! is_array($source) || ! is_string($source['url'] ?? null) || ! is_string($source['reference'] ?? null)
            || ! preg_match('~\Ahttps://github\.com/([A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+?)(?:\.git)?\z~', $source['url'], $repository)
            || ! preg_match('/\A[a-f0-9]{40}\z/', $source['reference'])) {
            return [];
        }

        $baseUrl = "https://raw.githubusercontent.com/{$repository[1]}/{$source['reference']}";
        $poolResponses = Http::pool(function (Pool $pool) use ($baseUrl): void {
            foreach (['.env.example', 'package-lock.json', 'bun.lock', 'bun.lockb'] as $file) {
                $request = $pool->as($file)->connectTimeout(3)->timeout(10)->withoutRedirecting();

                if ($file === '.env.example') {
                    $request->get("$baseUrl/$file");
                } else {
                    $request->head("$baseUrl/$file");
                }
            }
        });

        $responses = [];

        foreach ($poolResponses as $file => $response) {
            if ($response instanceof Throwable) {
                throw $response;
            }

            if (! $response->notFound()) {
                $response->throw();
                abort_unless($response->successful(), 502, 'Le dépôt du package a retourné une réponse inattendue.');
            }

            $responses[$file] = $response;
        }

        $detected = [];

        if ($responses['.env.example']->successful()) {
            $environment = Dotenv::parse($responses['.env.example']->body());
            $database = DatabaseEnum::tryFrom($environment['DB_CONNECTION'] ?? '');

            if ($database !== null) {
                $detected['database'] = $database->value;
            }

            $queueDriver = match ($environment['QUEUE_CONNECTION'] ?? null) {
                'database' => QueueDriverEnum::Database,
                'redis' => QueueDriverEnum::Redis,
                default => null,
            };

            if ($queueDriver !== null) {
                $detected['queue_type'] = QueueTypeEnum::Native->value;
                $detected['queue_driver'] = $queueDriver->value;
            }
        }

        foreach (['package-lock.json' => JavascriptPackageManagerEnum::Npm, 'bun.lock' => JavascriptPackageManagerEnum::Bun, 'bun.lockb' => JavascriptPackageManagerEnum::Bun] as $file => $manager) {
            if ($responses[$file]->successful()) {
                $detected['javascript_package_manager'] = $manager->value;

                break;
            }
        }

        return $detected;
    }
}
