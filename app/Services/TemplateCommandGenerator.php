<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\ProjectConfigurationData;
use App\Enums\DatabaseEnum;
use App\Enums\FeaturesEnum;
use App\Enums\JavascriptPackageManagerEnum;
use App\Enums\QueueDriverEnum;
use App\Enums\QueueTypeEnum;
use App\Enums\StarterKitEnum;
use App\Enums\TestingFrameworkEnum;
use InvalidArgumentException;

final class TemplateCommandGenerator
{
    /** @return list<string> */
    public function generate(ProjectConfigurationData $configuration): array
    {
        if (! preg_match(ProjectConfigurationData::PROJECT_NAME_PATTERN, $configuration->project_name)) {
            throw new InvalidArgumentException('Invalid DDEV project name.');
        }

        $commands = [];

        $baseCommand = 'ddev config --project-type=laravel --project-name=%s --php-version=%s --disable-upload-dirs-warning';
        $docroot = 'public';

        $database = $configuration->database;
        $databaseConfig = $database === DatabaseEnum::SQLite
            ? ' '
            : sprintf(' --database=%s', $database->getLatestVersion());

        $omitContainers = $database === DatabaseEnum::SQLite ? ' --omit-containers=db' : '';

        $commands[] = sprintf(
            $baseCommand.'%s --docroot=%s --disable-settings-management=true%s',
            escapeshellarg($configuration->project_name),
            $configuration->php_version->value,
            $databaseConfig,
            $docroot,
            $omitContainers
        );

        $commands[] = "ddev dotenv set .ddev/.env.web --database $database->value";

        $javascriptPackageManager = $configuration->javascript_package_manager;
        $commands[] = "ddev dotenv set .ddev/.env.web --javascript-package-manager $javascriptPackageManager->value";

        if ($javascriptPackageManager === JavascriptPackageManagerEnum::Bun) {
            $commands[] = 'ddev add-on get fouteox/ddev-bun';
        }

        $starterKitType = $configuration->starter_kit;

        if ($starterKitType === StarterKitEnum::None) {
            $commands[] = 'ddev composer create-project "laravel/laravel:^12" --remove-vcs --prefer-dist --no-scripts';
        } elseif ($starterKitType === StarterKitEnum::Custom) {
            $commands[] = 'ddev composer create-project '.escapeshellarg($configuration->custom_starter_kit ?? '').' --stability=dev --remove-vcs --prefer-dist --no-scripts';
        } else {
            if ($configuration->workos) {
                $commands[] = "ddev composer create-project laravel/{$starterKitType->value}-starter-kit:dev-workos --stability=dev --remove-vcs --prefer-dist --no-scripts";
            } elseif ($starterKitType === StarterKitEnum::Livewire && $configuration->livewire_volt) {
                $commands[] = "ddev composer create-project laravel/{$starterKitType->value}-starter-kit:dev-components --stability=dev --remove-vcs --prefer-dist --no-scripts";
            } else {
                $commands[] = "ddev composer create-project laravel/{$starterKitType->value}-starter-kit --stability=dev --remove-vcs --prefer-dist --no-scripts";
            }
        }

        $commands[] = 'ddev composer run post-root-package-install';
        $commands[] = 'ddev php artisan key:generate --ansi';

        $commands[] = 'rm -f package-lock.json';

        $commands[] = match ($configuration->javascript_package_manager) {
            JavascriptPackageManagerEnum::Npm => 'ddev npm install',
            JavascriptPackageManagerEnum::Bun => 'ddev bun install',
        };

        if ($database !== DatabaseEnum::SQLite) {
            $dbPort = $database->getDefaultPort();

            $commands[] = $this->updateEnvVariable('DB_CONNECTION', $database->value);
            $commands[] = $this->updateEnvVariable('DB_HOST', 'db');
            $commands[] = $this->updateEnvVariable('DB_PORT', (string) $dbPort);
            $commands[] = $this->updateEnvVariable('DB_DATABASE', 'db');
            $commands[] = $this->updateEnvVariable('DB_USERNAME', 'db');
            $commands[] = $this->updateEnvVariable('DB_PASSWORD', 'db');
        } else {
            $commands[] = 'ddev exec touch database/database.sqlite';
        }

        $commands[] = 'ddev exec php artisan migrate --no-interaction';

        if ($configuration->testing_framework === TestingFrameworkEnum::Pest) {
            $commands[] = 'ddev composer remove phpunit/phpunit --dev --no-update';
            $commands[] = 'ddev composer require pestphp/pest pestphp/pest-plugin-laravel --no-update --dev';
            $commands[] = 'ddev composer update';

            if ($starterKitType === StarterKitEnum::None) {
                $commands[] = 'ddev exec "rm tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php"';
            }

            $commands[] = 'ddev exec "PEST_NO_SUPPORT=true ./vendor/bin/pest --init"';

            if ($starterKitType !== StarterKitEnum::None) {
                $commands[] = 'ddev composer require pestphp/pest-plugin-drift --dev';
                $commands[] = 'ddev exec "./vendor/bin/pest --drift"';
                $commands[] = 'ddev composer remove pestphp/pest-plugin-drift --dev';

                $commands[] = 'ddev exec "perl -pi -e \'s|phpunit|pest|\' .github/workflows/tests.yml"';
            }
        }

        if ($configuration->queue_type !== null) {
            $driverAddon = match ($configuration->queue_driver) {
                QueueDriverEnum::Redis => 'fouteox/ddev-redis',
                QueueDriverEnum::Valkey => 'fouteox/ddev-valkey',
                QueueDriverEnum::Database, null => null,
            };

            if ($driverAddon !== null) {
                $commands[] = "ddev add-on get $driverAddon";

                $commands[] = 'ddev exec "perl -pi -e \'s|REDIS_HOST=127.0.0.1|REDIS_HOST=redis|; s|REDIS_PASSWORD=null|REDIS_PASSWORD=redis|; s|QUEUE_CONNECTION=database|QUEUE_CONNECTION=redis|\' .env .env.example"';
            }

            $queueAddon = match ($configuration->queue_type) {
                QueueTypeEnum::Native => 'fouteox/ddev-laravel-queue',
                QueueTypeEnum::Horizon => 'fouteox/ddev-laravel-horizon',
            };

            $commands[] = "ddev add-on get $queueAddon";

            if ($configuration->queue_type === QueueTypeEnum::Horizon) {
                $commands[] = 'ddev composer require laravel/horizon';
                $commands[] = 'ddev artisan horizon:install';
            }
        }

        if (in_array(FeaturesEnum::Schedule, $configuration->features, true)) {
            $commands[] = 'ddev add-on get fouteox/ddev-laravel-scheduling';
        }

        if (in_array(FeaturesEnum::Octane, $configuration->features, true)) {
            $commands[] = 'ddev add-on get fouteox/ddev-laravel-octane';
        }

        if (in_array(FeaturesEnum::Reverb, $configuration->features, true)) {
            $commands[] = 'ddev php artisan install:broadcasting --reverb --force --no-interaction';

            $commands[] = sprintf(
                'ddev exec "perl -pi -e \'s#REVERB_HOST=\"localhost\"#REVERB_HOST=\"%s.ddev.site\"#; s#REVERB_SCHEME=http#REVERB_SCHEME=https#\' .env"',
                $configuration->project_name
            );

            $commands[] = 'ddev exec "perl -pi -e \'s|BROADCAST_CONNECTION=log|BROADCAST_CONNECTION=reverb|\' .env.example"';

            $commands[] = sprintf(
                'ddev exec \'printf "\\nREVERB_APP_ID=123456\\nREVERB_APP_KEY=changeme123456\\nREVERB_APP_SECRET=changeme123456really\\nREVERB_HOST=\\"%s.ddev.site\\"\\nREVERB_PORT=8080\\nREVERB_SCHEME=https\\n\\nVITE_REVERB_APP_KEY=\\"\${REVERB_APP_KEY}\\"\\nVITE_REVERB_HOST=\\"\${REVERB_HOST}\\"\\nVITE_REVERB_PORT=\\"\${REVERB_PORT}\\"\\nVITE_REVERB_SCHEME=\\"\${REVERB_SCHEME}\\"" >> .env.example\'',
                $configuration->project_name
            );

            $commands[] = 'ddev add-on get fouteox/ddev-laravel-reverb';
        }

        $commands[] = 'ddev exec "perl -pi -e \'s|MAIL_MAILER=log|MAIL_MAILER="smtp"|; s|MAIL_PORT=2525|MAIL_PORT=1025|\' .env .env.example"';

        $commands[] = sprintf(
            'ddev exec "sed -i \'s#APP_URL=http://localhost#APP_URL=https://%s.ddev.site#\' .env .env.example"',
            $configuration->project_name
        );

        $commands[] = 'ddev add-on get fouteox/ddev-laravel-automation';

        $commands[] = 'ddev stop';

        $commands[] = 'ddev add-on get fouteox/ddev-vite';

        $commands[] = 'ddev add-on get fouteox/ddev-laravel-prepare';

        $commands[] = 'ddev restart && ddev launch';

        if ($configuration->initialize_git) {
            $commands[] = 'git init -q';
            $commands[] = 'git add .';
            $commands[] = 'git commit -q -m "Initial commit"';
        }

        return $commands;
    }

    private function updateEnvVariable(string $key, string $value): string
    {
        return sprintf(
            'ddev exec "perl -pi -e \'s|^#?\\s*%s=.*|%s=%s|\' .env .env.example"',
            preg_quote($key, '|'),
            $key,
            $value
        );
    }
}
