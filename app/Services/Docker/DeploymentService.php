<?php

declare(strict_types=1);

namespace App\Services\Docker;

use App\Enums\DatabaseEnum;
use App\Enums\QueueDriverEnum;
use App\Enums\QueueTypeEnum;
use App\Enums\StarterKitEnum;
use Random\RandomException;
use Symfony\Component\Yaml\Yaml;

final class DeploymentService
{
    private const string COMPOSE_PATH = __DIR__.'/../../DockerDeployment/stubs/compose.stub';

    private const string STUBS_DIR = __DIR__.'/../../DockerDeployment/stubs/';

    private const string DEPLOYMENTS_DIR = __DIR__.'/../../DockerDeployment/deployment/';

    /**
     * @throws RandomException
     */
    public function generateDeploymentFiles(array $config): array
    {
        $services = [];

        $selectedDb = DatabaseEnum::from($config['database']);
        $sqlite = $selectedDb === DatabaseEnum::SQLite;

        $php = $config['php_version'];

        // Queue configuration
        $queue = isset($config['queue_type']);
        $selectedQueueType = null;
        if ($queue) {
            $selectedQueueType = QueueTypeEnum::from($config['queue_type']);

            $services[] = mb_strtolower($selectedQueueType->value) === QueueTypeEnum::Horizon->value
                ? 'horizon'
                : 'worker';

            if ($selectedQueueType === QueueTypeEnum::Horizon) {
                // Horizon fonctionne avec Redis ou Valkey
                $queueDriver = QueueDriverEnum::from($config['queue_driver']);
                $services[] = $queueDriver->value;
            } elseif ($selectedQueueType === QueueTypeEnum::Native) {
                // Native peut fonctionner avec Database, Redis ou Valkey
                $queueDriver = QueueDriverEnum::from($config['queue_driver']);
                $services[] = $queueDriver->value;
            }
        }

        // Features and options
        $features = $config['features'] ?? [];
        $ssr = $config['starter_kit'] === StarterKitEnum::React->value || $config['starter_kit'] === StarterKitEnum::Vue->value;
        $node = $config['javascript_package_manager'] !== 'bun';
        $octane = in_array('octane', $features);
        $useReverb = in_array('reverb', $features);
        $useScheduler = in_array('schedule', $features);

        if (! $sqlite) {
            $services[] = mb_strtolower($selectedDb->value);
        }

        // Add Reverb if selected
        if ($useReverb) {
            $services[] = 'reverb';
        }

        // Add Scheduler if selected
        if ($useScheduler) {
            $services[] = 'scheduler';
        }

        $enabledServices = [];

        // Base configuration
        $composeConfig = $this->loadComposeFile();
        $supervisor = file_get_contents(self::DEPLOYMENTS_DIR.'supervisord.http.conf');
        $caddyfile = $octane
            ? file_get_contents(self::DEPLOYMENTS_DIR.'Caddyfile.octane')
            : file_get_contents(self::DEPLOYMENTS_DIR.'Caddyfile');

        if ($ssr && ! $node) {
            $supervisor = str_replace(
                'command = php %(ENV_ROOT)s/artisan inertia:start-ssr',
                'command = php %(ENV_ROOT)s/artisan inertia:start-ssr --runtime=bun',
                $supervisor
            );
        }

        if (! $octane) {
            $supervisor = str_replace(
                'command = php %(ENV_ROOT)s/artisan octane:frankenphp --port=8000 --admin-port=2019 --caddyfile=%(ENV_ROOT)s/deployment/Caddyfile',
                'command = frankenphp run --config %(ENV_ROOT)s/deployment/Caddyfile',
                $supervisor
            );
        }

        foreach ($services as $service) {
            $serviceConfig = $this->loadServiceFile($service);

            if ($serviceConfig) {
                $enabledServices[] = $service;
                $composeConfig = $this->mergeService($composeConfig, $service, $serviceConfig);
                $composeConfig = $this->mergeDependencies($composeConfig, $serviceConfig);
                $composeConfig = $this->mergeVolumes($composeConfig, $serviceConfig);
            }
        }

        if ($sqlite) {
            if (! isset($composeConfig['x-base']['volumes'])) {
                $composeConfig['x-base']['volumes'] = [];
            }
            $composeConfig['x-base']['volumes'][] = 'sqlite:/app/database/database';

            if (! isset($composeConfig['volumes'])) {
                $composeConfig['volumes'] = [];
            }
            $composeConfig['volumes']['sqlite'] = null;
        }

        $yaml = $this->dumpYaml($composeConfig);
        $yaml = $this->replaceReferences($yaml, $config);

        if (! $octane) {
            $yaml = str_replace('localhost:8000', 'localhost:80', $yaml);
            $yaml = str_replace('loadbalancer.server.port=8000', 'loadbalancer.server.port=80', $yaml);
        }

        $dockerfile = file_get_contents(self::STUBS_DIR.'Dockerfile.stub');

        if (! $sqlite) {
            $dockerfile = $this->removeDatabaseReferences($dockerfile);
        }

        $dockerfile = str_replace('PHP_VERSION=8.4', 'PHP_VERSION='.$php, $dockerfile);

        if (! $node) {
            $dockerfile = $this->replaceNodeWithBun($dockerfile);

            // Adjust build command based on SSR
            if (! $ssr) {
                $dockerfile = str_replace('RUN bun run build:ssr', 'RUN bun run build', $dockerfile);
            }
        } else {
            // If using Node but without SSR, adjust build command
            if (! $ssr) {
                $dockerfile = str_replace('RUN npm run build:ssr', 'RUN npm run build', $dockerfile);
            }
        }

        // Remove SSR bootstrap copy line if not using SSR
        if (! $ssr) {
            // Find and remove the line that copies SSR bootstrap files
            $ssrCopyPattern = '/COPY --link --chown=\$\{WWWUSER}:\$\{WWWGROUP} --from=build \$\{ROOT}\/bootstrap\/ssr bootstrap\/ssr\s*\n/';
            $dockerfile = preg_replace($ssrCopyPattern, '', $dockerfile);
            $yaml = str_replace('INERTIA_SSR: true', 'INERTIA_SSR: false', $yaml);
        }

        // Generate env.production file
        $envProduction = $this->generateEnvProduction(
            $selectedDb,
            $enabledServices
        );

        $files = [
            '.env.production' => $envProduction,
            'Dockerfile' => $dockerfile,
            'compose.prod.yaml' => $yaml,
            'supervisord.conf' => file_get_contents(self::DEPLOYMENTS_DIR.'supervisord.conf'),
            'supervisord.http.conf' => $supervisor,
            'Caddyfile' => $caddyfile,
            'deploy.yml' => file_get_contents(self::DEPLOYMENTS_DIR.'deploy.yml'),
        ];

        // Add supervisord files specific to enabled services
        if ($queue && $selectedQueueType === QueueTypeEnum::Horizon) {
            $files['supervisord.horizon.conf'] = file_get_contents(self::DEPLOYMENTS_DIR.'supervisord.horizon.conf');
        }

        if ($queue && $selectedQueueType === QueueTypeEnum::Native) {
            $files['supervisord.worker.conf'] = file_get_contents(self::DEPLOYMENTS_DIR.'supervisord.worker.conf');
        }

        if ($useReverb) {
            $files['supervisord.reverb.conf'] = file_get_contents(self::DEPLOYMENTS_DIR.'supervisord.reverb.conf');
        }

        if ($useScheduler) {
            $files['supervisord.scheduler.conf'] = file_get_contents(self::DEPLOYMENTS_DIR.'supervisord.scheduler.conf');
        }

        return $files;
    }

    /**
     * @throws RandomException
     */
    private function generateEnvProduction(
        DatabaseEnum $selectedDb,
        array $enabledServices
    ): string {
        $dbPassword = bin2hex(random_bytes(12));
        $redisPassword = bin2hex(random_bytes(12));
        $reverbAppId = mt_rand(100000, 999999);
        $reverbAppKey = bin2hex(random_bytes(16));
        $reverbAppSecret = bin2hex(random_bytes(16));

        // Base configuration
        $baseConfig = [
            'APP_NAME=Laravel',
            'VITE_APP_NAME="${APP_NAME}"',
            'APP_ENV=production',
            'APP_KEY=',
            'APP_DEBUG=false',
            'APP_HOST=example.com',
            'APP_URL="https://${APP_HOST}"',
            '',
            'LOG_CHANNEL=stack',
            'LOG_STACK=daily,errorlog',
            'LOG_DEPRECATIONS_CHANNEL=null',
            'LOG_LEVEL=error',
            '',
        ];

        $dbConfig = $selectedDb === DatabaseEnum::SQLite
            ? [
                'DB_CONNECTION=sqlite',
                'DB_DATABASE=/app/database/database/database.sqlite',
                '',
            ]
            : [
                'DB_CONNECTION='.match ($selectedDb) {
                    DatabaseEnum::Postgres => 'pgsql',
                    default => 'mysql',
                },
                'DB_HOST='.$selectedDb->value,
                'DB_PORT='.$selectedDb->getDefaultPort(),
                'DB_DATABASE=laravel',
                'DB_USERNAME=laravel',
                'DB_PASSWORD='.$dbPassword,
                '',
            ];

        $hasRedisService = in_array('valkey', $enabledServices) || in_array('redis', $enabledServices);
        $redisHost = in_array('valkey', $enabledServices) ? 'valkey' : 'redis';

        $cacheConfig = $hasRedisService
            ? [
                'REDIS_HOST='.$redisHost,
                'REDIS_PASSWORD='.$redisPassword,
                'CACHE_STORE=redis',
                'SESSION_DRIVER=redis',
                '',
            ]
            : [
                'CACHE_STORE=database',
                'SESSION_DRIVER=database',
                '',
            ];

        $useRedisQueue = in_array('horizon', $enabledServices) ||
            (in_array('worker', $enabledServices) && $hasRedisService);

        $queueConfig = [
            'QUEUE_CONNECTION='.($useRedisQueue ? 'redis' : 'database'),
            '',
        ];

        $reverbConfig = in_array('reverb', $enabledServices)
            ? [
                'BROADCAST_CONNECTION=reverb',
                'REVERB_APP_ID='.$reverbAppId,
                'REVERB_APP_KEY='.$reverbAppKey,
                'REVERB_APP_SECRET='.$reverbAppSecret,
                'REVERB_HOST="${APP_HOST}"',
                'REVERB_PORT=443',
                'REVERB_SCHEME=https',
                '',
                'VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"',
                'VITE_REVERB_HOST="${REVERB_HOST}"',
                'VITE_REVERB_PORT="${REVERB_PORT}"',
                'VITE_REVERB_SCHEME="${REVERB_SCHEME}"',
            ]
            : [];

        return implode("\n", array_merge(
            $baseConfig,
            $dbConfig,
            $cacheConfig,
            $queueConfig,
            $reverbConfig
        ));
    }

    private function loadComposeFile(): array
    {
        return Yaml::parseFile(self::COMPOSE_PATH);
    }

    private function loadServiceFile(string $service): ?array
    {
        $servicePath = self::STUBS_DIR.$service.'.stub';
        if (file_exists($servicePath)) {
            return Yaml::parseFile($servicePath);
        }

        return null;
    }

    private function mergeService(array $composeConfig, string $serviceName, array $serviceConfig): array
    {
        if (isset($serviceConfig[$serviceName])) {
            $composeConfig['services'][$serviceName] = $serviceConfig[$serviceName];
        }

        return $composeConfig;
    }

    private function mergeDependencies(array $composeConfig, array $serviceConfig): array
    {
        if (isset($serviceConfig['x-base']['depends_on'])) {
            if (! isset($composeConfig['x-base']['depends_on'])) {
                $composeConfig['x-base']['depends_on'] = [];
            }

            $composeConfig['x-base']['depends_on'] = array_merge(
                $composeConfig['x-base']['depends_on'],
                $serviceConfig['x-base']['depends_on']
            );
        }

        return $composeConfig;
    }

    private function mergeVolumes(array $composeConfig, array $serviceConfig): array
    {
        if (isset($serviceConfig['volumes'])) {
            if (! isset($composeConfig['volumes'])) {
                $composeConfig['volumes'] = [];
            }

            foreach ($serviceConfig['volumes'] as $volumeKey => $value) {
                $composeConfig['volumes'][$volumeKey] = $value;
            }
        }

        return $composeConfig;
    }

    private function dumpYaml(array $composeConfig): string
    {
        return Yaml::dump($composeConfig, 10, 2, Yaml::DUMP_OBJECT_AS_MAP);
    }

    private function replaceReferences(string $yaml, array $config): string
    {
        $yaml = preg_replace(
            '/x-logging:/m',
            'x-logging: &default-logging',
            $yaml
        );

        $yaml = preg_replace(
            '/x-network:/m',
            'x-network: &default-network',
            $yaml
        );

        $yaml = preg_replace(
            '/x-base:/m',
            'x-base: &base',
            $yaml
        );

        $yaml = preg_replace(
            '/__BASE_REFERENCE__: true/m',
            '<<: *base',
            $yaml
        );

        $yaml = preg_replace(
            '/__BASE_NETWORKS__: true/m',
            'networks: *default-network',
            $yaml
        );

        $projectName = preg_replace('/[^a-zA-Z0-9]/', '-', $config['project_name']);

        $yaml = preg_replace(
            '/__SERVICE_REFERENCE__/m',
            $projectName,
            $yaml
        );

        return preg_replace(
            '/__LOGGING_REFERENCE__: true/m',
            'logging: *default-logging',
            $yaml
        );
    }

    private function removeDatabaseReferences(string $content): string
    {
        // Split content into lines
        $lines = explode("\\\n", $content);

        // Filter out lines containing 'database'
        $filteredLines = [];
        foreach ($lines as $line) {
            if (! str_contains($line, 'database')) {
                $filteredLines[] = $line;
            } else {
                // For the last line (chmod -R), we need to keep it but remove database reference
                if (str_contains($line, 'chmod -R')) {
                    // Remove ${ROOT}/database/database from the chmod command
                    $line = preg_replace('/ \${ROOT}\/database\/database/', '', $line);
                    $filteredLines[] = $line;
                }
            }
        }

        // Rejoin lines
        return implode("\\\n", $filteredLines);
    }

    private function replaceNodeWithBun(string $content): string
    {
        $content = str_replace('NODE_VERSION', 'BUN_VERSION', $content);

        $content = preg_replace('/ARG BUN_VERSION=\d+/', 'ARG BUN_VERSION=1.2.16', $content);

        $content = preg_replace('/FROM node:\$\{BUN_VERSION}-alpine AS build/', 'FROM oven/bun:${BUN_VERSION} AS build', $content);

        $content = str_replace('COPY --link package*.json ./', 'COPY --link package.json bun.lock* ./', $content);

        $content = str_replace('RUN npm install', 'RUN bun install --frozen-lockfile', $content);

        return str_replace('RUN npm run build:ssr', 'RUN bun run build:ssr', $content);
    }
}
