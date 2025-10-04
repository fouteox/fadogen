<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\DatabaseEnum;
use App\Enums\FeaturesEnum;
use App\Enums\JavascriptPackageManagerEnum;
use App\Enums\QueueDriverEnum;
use App\Enums\QueueTypeEnum;
use App\Enums\StarterKitEnum;
use App\Enums\TemplateStatusEnum;
use App\Enums\TestingFrameworkEnum;
use App\Models\Template;
use App\Services\Docker\DeploymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PharData;
use RuntimeException;
use Throwable;

final class ProcessTemplateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $commands = [];

    public function __construct(private readonly Template $template) {}

    /**
     * @throws Throwable
     */
    public function handle(DeploymentService $deploymentService): void
    {
        try {
            $this->generateCommands();

            $this->generateDockerDeploymentFiles($deploymentService);

            $this->createArchive();

            $this->template->update(['status' => TemplateStatusEnum::Completed]);
        } catch (Throwable $e) {
            $this->template->update(['status' => TemplateStatusEnum::Failed]);
            throw $e;
        }
    }

    private function generateDockerDeploymentFiles(DeploymentService $deploymentService): void
    {
        $files = $deploymentService->generateDeploymentFiles($this->template->data);

        $templateId = $this->template->id;
        $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'build_'.$templateId.DIRECTORY_SEPARATOR.'deployment';

        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0777, true);
        }

        foreach ($files as $filename => $content) {
            File::put($tempDir.DIRECTORY_SEPARATOR.$filename, $content);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function createArchive(): void
    {
        $templateId = $this->template->id;
        $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'build_'.$templateId;

        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0777, true);
        }

        $commandsPath = $tempDir.DIRECTORY_SEPARATOR.'commands.sh';
        File::put($commandsPath, implode(PHP_EOL, $this->commands));

        $archivePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.$templateId.'.tar';

        $archive = new PharData($archivePath);
        $archive->buildFromDirectory($tempDir);

        File::delete($commandsPath);
        File::deleteDirectory($tempDir);

        Storage::put($templateId.'.tar', File::get($archivePath));

        File::delete($archivePath);
    }

    private function installNodeDependencies(): void
    {
        if (! isset($this->template->data['javascript_package_manager'])) {
            return;
        }

        $this->commands[] = match ($this->template->data['javascript_package_manager']) {
            JavascriptPackageManagerEnum::Npm->value => 'ddev npm install',
            JavascriptPackageManagerEnum::Bun->value => 'ddev bun install',
            default => throw new RuntimeException('Package manager non supporté')
        };
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

    private function generateCommands(): void
    {
        $config = $this->template->data;

        $baseCommand = 'ddev config --project-type=laravel --project-name=%s --php-version=%s --disable-upload-dirs-warning';
        $docroot = 'public';

        $database = DatabaseEnum::from($config['database']);
        $databaseConfig = $database === DatabaseEnum::SQLite
            ? ' '
            : sprintf(' --database=%s', $database->getLatestVersion());

        $omitContainers = $database === DatabaseEnum::SQLite ? ' --omit-containers=db' : '';

        $this->commands[] = sprintf(
            $baseCommand.'%s --docroot=%s --disable-settings-management=true%s',
            $config['project_name'],
            $config['php_version'],
            $databaseConfig,
            $docroot,
            $omitContainers
        );

        $this->commands[] = "ddev dotenv set .ddev/.env.web --database $database->value";

        $starterKitType = $config['starter_kit'];

        if ($starterKitType === StarterKitEnum::None->value) {
            $this->commands[] = 'ddev composer create-project "laravel/laravel:^12" --remove-vcs --prefer-dist --no-scripts';
        } elseif ($starterKitType === StarterKitEnum::Custom->value) {
            $this->commands[] = "ddev composer create-project {$config['custom_starter_kit']} --stability=dev --remove-vcs --prefer-dist --no-scripts";
        } else {
            if ($config['workos']) {
                $this->commands[] = "ddev composer create-project laravel/$starterKitType-starter-kit:dev-workos --stability=dev --remove-vcs --prefer-dist --no-scripts";
            } elseif ($starterKitType === StarterKitEnum::Livewire->value && $config['livewire_volt']) {
                $this->commands[] = "ddev composer create-project laravel/$starterKitType-starter-kit:dev-components --stability=dev --remove-vcs --prefer-dist --no-scripts";
            } else {
                $this->commands[] = "ddev composer create-project laravel/$starterKitType-starter-kit --stability=dev --remove-vcs --prefer-dist --no-scripts";
            }
        }

        $this->commands[] = 'ddev composer run post-root-package-install';
        $this->commands[] = 'ddev php artisan key:generate --ansi';

        if (isset($config['javascript_package_manager'])) {
            $javascriptPackageManager = $config['javascript_package_manager'];
            $this->commands[] = "ddev dotenv set .ddev/.env.web --javascript-package-manager $javascriptPackageManager";

            if ($config['javascript_package_manager'] === JavascriptPackageManagerEnum::Bun->value) {
                $this->commands[] = 'ddev add-on get fouteox/ddev-bun';
            }
        }

        $this->installNodeDependencies();

        if ($config['database'] !== DatabaseEnum::SQLite->value) {
            $dbPort = $database->getDefaultPort();

            $this->commands[] = $this->updateEnvVariable('DB_CONNECTION', $config['database']);
            $this->commands[] = $this->updateEnvVariable('DB_HOST', 'db');
            $this->commands[] = $this->updateEnvVariable('DB_PORT', (string) $dbPort);
            $this->commands[] = $this->updateEnvVariable('DB_DATABASE', 'db');
            $this->commands[] = $this->updateEnvVariable('DB_USERNAME', 'db');
            $this->commands[] = $this->updateEnvVariable('DB_PASSWORD', 'db');
        } else {
            $this->commands[] = 'ddev exec touch database/database.sqlite';
        }

        $this->commands[] = 'ddev exec php artisan migrate --no-interaction';

        if ($config['testing_framework'] === TestingFrameworkEnum::Pest->value) {
            $this->commands[] = 'ddev composer remove phpunit/phpunit --dev --no-update';
            $this->commands[] = 'ddev composer require pestphp/pest pestphp/pest-plugin-laravel --no-update --dev';
            $this->commands[] = 'ddev composer update';

            if ($starterKitType === StarterKitEnum::None->value) {
                $this->commands[] = 'ddev exec "rm tests/Feature/ExampleTest.php tests/Unit/ExampleTest.php"';
            }

            $this->commands[] = 'ddev exec "PEST_NO_SUPPORT=true ./vendor/bin/pest --init"';

            if ($starterKitType !== StarterKitEnum::None->value) {
                $this->commands[] = 'ddev composer require pestphp/pest-plugin-drift --dev';
                $this->commands[] = 'ddev exec "./vendor/bin/pest --drift"';
                $this->commands[] = 'ddev composer remove pestphp/pest-plugin-drift --dev';

                $this->commands[] = 'ddev exec "perl -pi -e \'s|phpunit|pest|\' .github/workflows/tests.yml"';
            }
        }

        if (isset($config['queue_type'])) {
            $queueDriver = $config['queue_driver'] ?? null;
            $queueType = $config['queue_type'];

            $driverAddons = [
                QueueDriverEnum::Redis->value => 'fouteox/ddev-redis',
                QueueDriverEnum::Valkey->value => 'fouteox/ddev-valkey',
            ];

            if ($queueDriver !== QueueDriverEnum::Database->value && isset($driverAddons[$queueDriver])) {
                $this->commands[] = "ddev add-on get $driverAddons[$queueDriver]";

                $this->commands[] = 'ddev exec "perl -pi -e \'s|REDIS_HOST=127.0.0.1|REDIS_HOST=redis|; s|REDIS_PASSWORD=null|REDIS_PASSWORD=redis|; s|QUEUE_CONNECTION=database|QUEUE_CONNECTION=redis|\' .env .env.example"';
            }

            $queueAddon = match ($queueType) {
                QueueTypeEnum::Native->value => 'fouteox/ddev-laravel-queue',
                default => 'fouteox/ddev-laravel-horizon'
            };

            $this->commands[] = "ddev add-on get $queueAddon";

            if ($queueType !== QueueTypeEnum::Native->value) {
                $this->commands[] = 'ddev composer require laravel/horizon';
                $this->commands[] = 'ddev artisan horizon:install';
            }
        }

        if (in_array(FeaturesEnum::Schedule->value, $config['features'])) {
            $this->commands[] = 'ddev add-on get fouteox/ddev-laravel-scheduling';
        }

        if (in_array(FeaturesEnum::Octane->value, $config['features'])) {
            $this->commands[] = 'ddev add-on get fouteox/ddev-laravel-octane';
        }

        if (in_array(FeaturesEnum::Reverb->value, $config['features'])) {
            $this->commands[] = 'ddev php artisan install:broadcasting --reverb --force --no-interaction';

            $this->commands[] = sprintf(
                'ddev exec "perl -pi -e \'s#REVERB_HOST=\"localhost\"#REVERB_HOST=\"%s.ddev.site\"#; s#REVERB_SCHEME=http#REVERB_SCHEME=https#\' .env"',
                $config['project_name']
            );

            $this->commands[] = 'ddev exec "perl -pi -e \'s|BROADCAST_CONNECTION=log|BROADCAST_CONNECTION=reverb|\' .env.example"';

            $this->commands[] = sprintf(
                'ddev exec \'printf "\\nREVERB_APP_ID=123456\\nREVERB_APP_KEY=changeme123456\\nREVERB_APP_SECRET=changeme123456really\\nREVERB_HOST=\\"%s.ddev.site\\"\\nREVERB_PORT=8080\\nREVERB_SCHEME=https\\n\\nVITE_REVERB_APP_KEY=\\"\${REVERB_APP_KEY}\\"\\nVITE_REVERB_HOST=\\"\${REVERB_HOST}\\"\\nVITE_REVERB_PORT=\\"\${REVERB_PORT}\\"\\nVITE_REVERB_SCHEME=\\"\${REVERB_SCHEME}\\"" >> .env.example\'',
                $config['project_name']
            );

            $this->commands[] = 'ddev add-on get fouteox/ddev-laravel-reverb';
        }

        $this->commands[] = 'ddev exec "perl -pi -e \'s|MAIL_MAILER=log|MAIL_MAILER="smtp"|; s|MAIL_PORT=2525|MAIL_PORT=1025|\' .env .env.example"';

        $this->commands[] = sprintf(
            'ddev exec "sed -i \'s#APP_URL=http://localhost#APP_URL=https://%s.ddev.site#\' .env .env.example"',
            $config['project_name']
        );

        $this->commands[] = 'ddev add-on get fouteox/ddev-laravel-automation';

        $this->commands[] = 'ddev stop';

        $this->commands[] = 'ddev add-on get fouteox/ddev-vite';

        $this->commands[] = 'ddev add-on get fouteox/ddev-laravel-prepare';

        $this->commands[] = 'ddev restart && ddev launch';

        if ($config['initialize_git']) {
            $this->commands[] = 'git init -q';
            $this->commands[] = 'git add .';
            $this->commands[] = 'git commit -q -m "Initial commit"';
        }
    }
}
