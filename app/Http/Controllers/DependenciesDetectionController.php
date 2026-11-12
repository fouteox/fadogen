<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\ProjectConfigurationData;
use App\Enums\DatabaseEnum;
use App\Enums\FeaturesEnum;
use App\Enums\JavascriptPackageManagerEnum;
use App\Enums\PhpVersionEnum;
use App\Enums\QueueDriverEnum;
use App\Enums\QueueTypeEnum;
use App\Enums\TestingFrameworkEnum;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DependenciesDetectionController extends Controller
{
    /**
     * Fichiers lus dans le dépôt du starter kit, chacun sur les branches main
     * puis master.
     */
    private const array REPOSITORY_FILES = ['composer.json', '.env.example', 'package-lock.json', 'bun.lock', 'bun.lockb'];

    private const array REPOSITORY_BRANCHES = ['main', 'master'];

    public function detect(Request $request): JsonResponse
    {
        if (! $request->filled('package')) {
            return response()->json();
        }

        $package = $request->validate([
            'package' => ['string', 'max:255', 'regex:'.ProjectConfigurationData::COMPOSER_PACKAGE_PATTERN],
        ])['package'];

        $detectedDependencies = [
            'features' => [],
        ];

        $files = $this->fetchRepositoryFiles($package);

        $composerData = isset($files['composer.json']) ? json_decode($files['composer.json'], true) : null;

        if (! is_array($composerData)) {
            Log::info("Unable to read composer.json from repository $package");

            return response()->json(['detected' => $detectedDependencies]);
        }

        $this->analyseComposerData($composerData, $detectedDependencies);

        if (isset($files['.env.example'])) {
            $this->detectDatabaseFromEnvExample($files['.env.example'], $detectedDependencies);
        }

        if (isset($files['package-lock.json'])) {
            $detectedDependencies['javascript_package_manager'] = JavascriptPackageManagerEnum::Npm->value;
        } elseif (isset($files['bun.lock']) || isset($files['bun.lockb'])) {
            $detectedDependencies['javascript_package_manager'] = JavascriptPackageManagerEnum::Bun->value;
        }

        return response()->json(['detected' => $detectedDependencies]);
    }

    /**
     * Lit en une seule passe concurrente et bornée dans le temps les fichiers
     * utiles du dépôt : un worker Octane ne reste jamais bloqué sur GitHub.
     *
     * @return array<string, string> Contenu par nom de fichier, branche main en priorité.
     */
    private function fetchRepositoryFiles(string $package): array
    {
        $responses = Http::pool(function (Pool $pool) use ($package): array {
            $requests = [];

            foreach (self::REPOSITORY_FILES as $file) {
                foreach (self::REPOSITORY_BRANCHES as $branch) {
                    $requests[] = $pool->as("$branch:$file")
                        ->connectTimeout(2)
                        ->timeout(4)
                        ->get("https://raw.githubusercontent.com/$package/$branch/$file");
                }
            }

            return $requests;
        });

        $files = [];

        foreach (self::REPOSITORY_FILES as $file) {
            foreach (self::REPOSITORY_BRANCHES as $branch) {
                $response = $responses["$branch:$file"] ?? null;

                if ($response instanceof Response && $response->successful()) {
                    $files[$file] = $response->body();

                    break;
                }
            }
        }

        return $files;
    }

    /**
     * Detect database type from .env.example file
     *
     * @param  array<string, mixed>  $detectedDependencies
     */
    private function detectDatabaseFromEnvExample(string $envContent, array &$detectedDependencies): void
    {
        if (preg_match('/DB_CONNECTION=(\S+)/i', $envContent, $matches)) {
            $database = match (mb_strtolower(mb_trim($matches[1]))) {
                'mysql' => DatabaseEnum::Mysql,
                'pgsql' => DatabaseEnum::Postgres,
                'sqlite' => DatabaseEnum::SQLite,
                'mariadb' => DatabaseEnum::Mariadb,
                default => null,
            };

            if ($database !== null) {
                $detectedDependencies['database'] = $database->value;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $composerData
     * @param  array<string, mixed>  $detectedDependencies
     */
    private function analyseComposerData(array $composerData, array &$detectedDependencies): void
    {
        $require = is_array($composerData['require'] ?? null) ? $composerData['require'] : [];
        $requireDev = is_array($composerData['require-dev'] ?? null) ? $composerData['require-dev'] : [];
        $allDependencies = array_merge($require, $requireDev);

        if (is_string($allDependencies['php'] ?? null)) {
            $phpVersion = $this->extractPhpVersion($allDependencies['php']);
            if ($phpVersion) {
                $detectedDependencies['php_version'] = $phpVersion;
            }
        }

        $this->detectLimitedDependencies($allDependencies, $detectedDependencies);
    }

    /**
     * @param  array<string, mixed>  $dependencies
     * @param  array<string, mixed>  $detectedDependencies
     */
    private function detectLimitedDependencies(array $dependencies, array &$detectedDependencies): void
    {
        if (isset($dependencies['laravel/horizon'])) {
            $detectedDependencies['queue_type'] = QueueTypeEnum::Horizon->value;

            if (isset($dependencies['predis/predis']) || isset($dependencies['laravel/valkey'])) {
                $detectedDependencies['queue_driver'] = isset($dependencies['laravel/valkey'])
                    ? QueueDriverEnum::Valkey->value
                    : QueueDriverEnum::Redis->value;
            }
        } elseif (isset($dependencies['laravel/queues'])) {
            $detectedDependencies['queue_type'] = QueueTypeEnum::Native->value;
        }

        if (isset($dependencies['laravel/reverb'])) {
            $detectedDependencies['features'][] = FeaturesEnum::Reverb->value;
        }

        if (isset($dependencies['laravel/octane'])) {
            $detectedDependencies['features'][] = FeaturesEnum::Octane->value;
        }

        if (isset($dependencies['pestphp/pest'])) {
            $detectedDependencies['testing_framework'] = TestingFrameworkEnum::Pest->value;
        } elseif (isset($dependencies['phpunit/phpunit'])) {
            $detectedDependencies['testing_framework'] = TestingFrameworkEnum::PHPUnit->value;
        }
    }

    private function extractPhpVersion(string $constraint): ?string
    {
        $versions = explode('|', $constraint);
        $highestSupportedVersion = null;

        foreach ($versions as $versionConstraint) {
            $versionConstraint = mb_trim($versionConstraint);

            if (preg_match('/(\d+\.\d+)/', $versionConstraint, $matches)) {
                $phpVersion = PhpVersionEnum::tryFrom($matches[1]);

                if ($phpVersion !== null && ($highestSupportedVersion === null || version_compare($phpVersion->value, $highestSupportedVersion->value, '>'))) {
                    $highestSupportedVersion = $phpVersion;
                }
            }
        }

        return $highestSupportedVersion?->value;
    }
}
