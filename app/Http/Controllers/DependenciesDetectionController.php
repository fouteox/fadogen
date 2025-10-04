<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\DatabaseEnum;
use App\Enums\FeaturesEnum;
use App\Enums\JavascriptPackageManagerEnum;
use App\Enums\PhpVersionEnum;
use App\Enums\QueueDriverEnum;
use App\Enums\QueueTypeEnum;
use App\Enums\TestingFrameworkEnum;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class DependenciesDetectionController extends Controller
{
    public function detect(): JsonResponse
    {
        $customPackage = request()->input('package');

        $detectedDependencies = [
            'features' => [],
        ];

        if (! $customPackage) {
            return response()->json();
        }

        try {
            $packageData = $this->analyseCustomPackage($customPackage, $detectedDependencies);

            if ($packageData) {
                $this->analyseRepositoryFiles($customPackage, $detectedDependencies);
            }
        } catch (Exception $e) {
            Log::error("Error analyzing package $customPackage: ".$e->getMessage());
        }

        return response()->json(['detected' => $detectedDependencies]);
    }

    /**
     * Analyze GitHub repository configuration files
     */
    private function analyseRepositoryFiles(string $package, array &$detectedDependencies): void
    {
        try {
            [$vendor, $repo] = explode('/', $package, 2);

            $envExampleUrl = "https://raw.githubusercontent.com/$vendor/$repo/main/.env.example";
            $response = Http::get($envExampleUrl);

            if ($response->successful()) {
                $this->detectDatabaseFromEnvExample($response->body(), $detectedDependencies);
            } else {
                $envExampleUrl = "https://raw.githubusercontent.com/$vendor/$repo/master/.env.example";
                $response = Http::get($envExampleUrl);

                if ($response->successful()) {
                    $this->detectDatabaseFromEnvExample($response->body(), $detectedDependencies);
                }
            }

            $this->detectJsPackageManager($vendor, $repo, $detectedDependencies);

        } catch (Exception $e) {
            Log::info("Unable to analyze repository files for $package: ".$e->getMessage());
        }
    }

    /**
     * Detect database type from .env.example file
     */
    private function detectDatabaseFromEnvExample(string $envContent, array &$detectedDependencies): void
    {
        if (preg_match('/DB_CONNECTION=(\S+)/i', $envContent, $matches)) {
            $dbConnection = mb_strtolower(mb_trim($matches[1]));

            switch ($dbConnection) {
                case 'mysql':
                    $detectedDependencies['database'] = DatabaseEnum::Mysql->value;
                    break;
                case 'pgsql':
                    $detectedDependencies['database'] = DatabaseEnum::Postgres->value;
                    break;
                case 'sqlite':
                    $detectedDependencies['database'] = DatabaseEnum::SQLite->value;
                    break;
                case 'mariadb':
                    $detectedDependencies['database'] = DatabaseEnum::Mariadb->value;
                    break;
            }
        }
    }

    /**
     * Detect JavaScript package manager by checking lock files existence
     *
     * @throws ConnectionException
     */
    private function detectJsPackageManager(string $vendor, string $repo, array &$detectedDependencies): void
    {
        $npmLockUrl = "https://raw.githubusercontent.com/$vendor/$repo/main/package-lock.json";
        $npmResponse = Http::get($npmLockUrl);

        if ($npmResponse->successful()) {
            $detectedDependencies['javascript_package_manager'] = JavascriptPackageManagerEnum::Npm->value;

            return;
        }

        $npmLockUrl = "https://raw.githubusercontent.com/$vendor/$repo/master/package-lock.json";
        $npmResponse = Http::get($npmLockUrl);

        if ($npmResponse->successful()) {
            $detectedDependencies['javascript_package_manager'] = JavascriptPackageManagerEnum::Npm->value;

            return;
        }

        $bunLockUrl = "https://raw.githubusercontent.com/$vendor/$repo/main/bun.lockb";
        $bunResponse = Http::head($bunLockUrl);

        if ($bunResponse->successful()) {
            $detectedDependencies['javascript_package_manager'] = JavascriptPackageManagerEnum::Bun->value;

            return;
        }

        $bunLockUrl = "https://raw.githubusercontent.com/$vendor/$repo/master/bun.lockb";
        $bunResponse = Http::head($bunLockUrl);

        if ($bunResponse->successful()) {
            $detectedDependencies['javascript_package_manager'] = JavascriptPackageManagerEnum::Bun->value;
        }
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    private function analyseCustomPackage(string $package, array &$detectedDependencies): ?array
    {
        [$vendor, $repo] = explode('/', $package, 2);

        $composerUrl = "https://raw.githubusercontent.com/$vendor/$repo/main/composer.json";
        $response = Http::get($composerUrl);

        if (! $response->successful()) {
            $composerUrl = "https://raw.githubusercontent.com/$vendor/$repo/master/composer.json";
            $response = Http::get($composerUrl);

            if (! $response->successful()) {
                throw new Exception("Unable to retrieve composer.json from repository $package");
            }
        }

        $composerData = $response->json();

        if (! $composerData) {
            throw new Exception("Invalid composer.json format for $package");
        }

        $require = $composerData['require'] ?? [];
        $requireDev = $composerData['require-dev'] ?? [];
        $allDependencies = array_merge($require, $requireDev);

        if (isset($allDependencies['php'])) {
            $phpVersion = $this->extractPhpVersion($allDependencies['php']);
            if ($phpVersion) {
                $detectedDependencies['php_version'] = $phpVersion;
            }
        }

        $this->detectLimitedDependencies($allDependencies, $detectedDependencies);

        return $composerData;
    }

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
        $highestVersion = null;

        foreach ($versions as $versionConstraint) {
            $versionConstraint = mb_trim($versionConstraint);

            if (preg_match('/(\d+\.\d+)/', $versionConstraint, $matches)) {
                $version = $matches[1];

                if ($highestVersion === null || version_compare($version, $highestVersion, '>')) {
                    $highestVersion = $version;
                }
            }
        }

        if ($highestVersion !== null) {
            if (version_compare($highestVersion, '8.4', '>=')) {
                return PhpVersionEnum::Php84->value;
            }
            if (version_compare($highestVersion, '8.3', '>=')) {
                return PhpVersionEnum::Php83->value;
            }
            if (version_compare($highestVersion, '8.2', '>=')) {
                return PhpVersionEnum::Php82->value;
            }
        }

        return null;
    }
}
