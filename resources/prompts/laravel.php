<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

final class Laravel
{
    private array $answers = [
        'project_name' => 'laravel',
        'php_version' => '8.4',
        'database' => 'sqlite',
        'starter_kit' => 'none',
        'livewire_volt' => false,
        'workos' => false,
        'testing_framework' => 'pest',
        'queue' => 'none',
        'queue_driver' => '',
        'features' => [],
        'javascript_package_manager' => '',
        'initialize_git' => true,
    ];

    public function run(): array
    {
        $this->promptForBasicQuestions();
        $this->promptForDatabase();
        $this->promptForStarterKit();
        $this->promptForQueue();
        $this->promptForFeatures();
        $this->promptForJavascriptPackageManager();
        $this->promptForInitializeGit();

        return $this->formatOutput();
    }

    private function formatOutput(): array
    {
        $output = [
            'project_name' => $this->answers['project_name'],
            'php_version' => $this->answers['php_version'],
            'database' => $this->answers['database'],
            'starter_kit' => $this->answers['starter_kit'],
            'testing_framework' => $this->answers['testing_framework'],
            'features' => $this->answers['features'],
            'initialize_git' => $this->answers['initialize_git'],
        ];

        if (! empty($this->answers['starter_kit_stack'])) {
            $output['starter_kit_stack'] = $this->answers['starter_kit_stack'];
        }

        if ($this->answers['livewire_volt']) {
            $output['livewire_volt'] = $this->answers['livewire_volt'];
        }

        if ($this->answers['queue'] !== 'none') {
            $output['queue_type'] = $this->answers['queue'] === 'queue' ? 'native' : 'horizon';
            $output['queue_driver'] = $this->answers['queue_driver'];
        }

        $output['javascript_package_manager'] = $this->answers['javascript_package_manager'];

        return $output;
    }

    private function promptForBasicQuestions(): void
    {
        $isValidProjectName = false;

        while (! $isValidProjectName) {
            $projectName = text(
                label: __('laravel.name_project'),
                default: $this->answers['project_name'],
                required: true,
                validate: function (string $value): ?string {
                    if (! preg_match('/^[a-zA-Z0-9-_]+$/', $value)) {
                        return __('laravel.name_project_validation');
                    }

                    $projectPath = '/app/dir/'.$value;
                    if (is_dir($projectPath)) {
                        return __('laravel.name_project_exists', ['name' => $value]);
                    }

                    return null;
                }
            );

            $this->answers['project_name'] = $projectName;
            $isValidProjectName = true;
        }

        $this->answers['php_version'] = select(
            label: __('laravel.php_version'),
            options: [
                '8.4' => 'PHP 8.4 ('.__('Recommended').')',
                '8.3' => 'PHP 8.3',
                '8.2' => 'PHP 8.2',
            ]
        );
    }

    private function promptForDatabase(): void
    {
        $this->answers['database'] = select(
            label: __('laravel.database'),
            options: [
                'sqlite' => 'SQLite',
                'mysql' => 'MySQL',
                'mariadb' => 'MariaDB',
                'pgsql' => 'PostgreSQL',
            ]
        );
    }

    private function promptForStarterKit(): void
    {
        $this->answers['starter_kit'] = select(
            label: __('laravel.starter_kit'),
            options: [
                'none' => __('laravel.starter_kit_none'),
                'react' => 'React',
                'vue' => 'Vue',
                'livewire' => 'Livewire',
            ]
        );

        if ($this->answers['starter_kit'] !== 'none') {
            $this->promptForAuth();
        }

        if (! $this->answers['workos'] && $this->answers['starter_kit'] === 'livewire') {
            $this->answers['livewire_volt'] = confirm(label: __('Would you like to use Laravel Volt?'), default: false);
        }

        $this->promptForTestingFramework();
    }

    private function promptForTestingFramework(): void
    {
        $this->answers['testing_framework'] = mb_strtolower(select(
            label: __('laravel.testing_framework'),
            options: ['Pest', 'PHPUnit']
        ));
    }

    private function promptForQueue(): void
    {
        $this->answers['queue'] = select(
            label: __('laravel.queue_service'),
            options: [
                'none' => __('None'),
                'horizon' => 'Horizon ('.__('Recommended').')',
                'queue' => 'Queues native',
            ]
        );

        if ($this->answers['queue'] === 'queue') {
            $this->answers['queue_driver'] = select(
                label: __('laravel.queue_driver'),
                options: [
                    'valkey' => 'Valkey ('.__('Recommended').')',
                    'redis' => 'Redis',
                    'database' => 'Database',
                ]
            );
        } elseif ($this->answers['queue'] === 'horizon') {
            $this->answers['queue_driver'] = select(
                label: __('laravel.queue_driver'),
                options: [
                    'valkey' => 'Valkey ('.__('Recommended').')',
                    'redis' => 'Redis',
                ]
            );
        }
    }

    private function promptForFeatures(): void
    {
        $this->answers['features'] = multiselect(
            label: __('laravel.optional_features'),
            options: [
                'schedule' => 'Task Scheduling',
                'reverb' => 'Reverb',
                'octane' => __('Octane with FrankenPHP'),
            ]
        );
    }

    private function promptForJavascriptPackageManager(): void
    {
        $this->answers['javascript_package_manager'] = select(
            label: __('laravel.javascript_package_manager'),
            options: [
                'npm',
                //                'yarn',
                //                'pnpm',
                'bun',
            ]
        );
    }

    private function promptForInitializeGit(): void
    {
        $this->answers['initialize_git'] = confirm(label: __('laravel.initialize_git'));
    }

    private function promptForAuth(): void
    {
        $this->answers['workos'] = match (select(
            label: __('laravel.authentication_provider'),
            options: [
                'laravel' => __('laravel.laravel_auth'),
                'workos' => __('laravel.workos'),
            ],
            default: 'laravel',
        )) {
            'laravel' => false,
            'workos' => true,
            default => null,
        };
    }
}

$questionTree = new Laravel();
$answers = $questionTree->run();

file_put_contents('/app/output/result.json', json_encode($answers, JSON_PRETTY_PRINT));
