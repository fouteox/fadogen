{!! "<?php" !!}

declare(strict_types=1);

require 'vendor/autoload.php';

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

final class LaravelConfiguration
{
    private array $answers = [
        'project_name' => 'laravel',
        'php_version' => '8.5',
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
        return [
            'project_name' => $this->answers['project_name'],
            'php_version' => $this->answers['php_version'],
            'database' => $this->answers['database'],
            'starter_kit' => $this->answers['starter_kit'],
            'custom_starter_kit' => null,
            'livewire_volt' => $this->answers['livewire_volt'],
            'workos' => $this->answers['workos'],
            'testing_framework' => $this->answers['testing_framework'],
            'queue_type' => match ($this->answers['queue']) {
                'queue' => 'native',
                'horizon' => 'horizon',
                default => null,
            },
            'queue_driver' => $this->answers['queue'] === 'none' ? null : $this->answers['queue_driver'],
            'features' => $this->answers['features'],
            'javascript_package_manager' => $this->answers['javascript_package_manager'],
            'initialize_git' => $this->answers['initialize_git'],
        ];
    }

    private function promptForBasicQuestions(): void
    {
        $this->answers['project_name'] = text(
            label: {!! var_export(__('laravel.name_project'), true) !!},
            default: $this->answers['project_name'],
            required: true,
            validate: function (string $value): ?string {
                if (strlen($value) > 255 || ! preg_match({!! var_export($projectNamePattern, true) !!}, $value)) {
                    return {!! var_export(__('laravel.name_project_validation'), true) !!};
                }

                $projectPath = '/app/dir/'.$value;
                if (is_dir($projectPath)) {
                    return strtr({!! var_export(__('laravel.name_project_exists'), true) !!}, [':name' => $value]);
                }

                return null;
            }
        );

        $phpVersionOptions = {!! var_export($phpVersionOptions, true) !!};

        $this->answers['php_version'] = select(
            label: {!! var_export(__('laravel.php_version'), true) !!},
            options: $phpVersionOptions,
        );
    }

    private function promptForDatabase(): void
    {
        $this->answers['database'] = select(
            label: {!! var_export(__('laravel.database'), true) !!},
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
            label: {!! var_export(__('laravel.starter_kit'), true) !!},
            options: [
                'none' => {!! var_export(__('laravel.starter_kit_none'), true) !!},
                'react' => 'React',
                'vue' => 'Vue',
                'livewire' => 'Livewire',
            ]
        );

        if ($this->answers['starter_kit'] !== 'none') {
            $this->promptForAuth();
        }

        if (! $this->answers['workos'] && $this->answers['starter_kit'] === 'livewire') {
            $this->answers['livewire_volt'] = confirm(label: {!! var_export(__('Would you like to use Laravel Volt?'), true) !!}, default: false);
        }

        $this->promptForTestingFramework();
    }

    private function promptForTestingFramework(): void
    {
        $this->answers['testing_framework'] = mb_strtolower(select(
            label: {!! var_export(__('laravel.testing_framework'), true) !!},
            options: ['Pest', 'PHPUnit']
        ));
    }

    private function promptForQueue(): void
    {
        $this->answers['queue'] = select(
            label: {!! var_export(__('laravel.queue_service'), true) !!},
            options: [
                'none' => {!! var_export(__('None'), true) !!},
                'horizon' => 'Horizon ('.{!! var_export(__('Recommended'), true) !!}.')',
                'queue' => 'Queues native',
            ]
        );

        if ($this->answers['queue'] === 'queue') {
            $this->answers['queue_driver'] = select(
                label: {!! var_export(__('laravel.queue_driver'), true) !!},
                options: [
                    'valkey' => 'Valkey ('.{!! var_export(__('Recommended'), true) !!}.')',
                    'redis' => 'Redis',
                    'database' => 'Database',
                ]
            );
        } elseif ($this->answers['queue'] === 'horizon') {
            $this->answers['queue_driver'] = select(
                label: {!! var_export(__('laravel.queue_driver'), true) !!},
                options: [
                    'valkey' => 'Valkey ('.{!! var_export(__('Recommended'), true) !!}.')',
                    'redis' => 'Redis',
                ]
            );
        }
    }

    private function promptForFeatures(): void
    {
        $this->answers['features'] = multiselect(
            label: {!! var_export(__('laravel.optional_features'), true) !!},
            options: [
                'schedule' => 'Task Scheduling',
                'reverb' => 'Reverb',
                'octane' => {!! var_export(__('Octane with FrankenPHP'), true) !!},
            ]
        );
    }

    private function promptForJavascriptPackageManager(): void
    {
        $this->answers['javascript_package_manager'] = select(
            label: {!! var_export(__('laravel.javascript_package_manager'), true) !!},
            options: [
                'npm',
                'bun',
            ]
        );
    }

    private function promptForInitializeGit(): void
    {
        $this->answers['initialize_git'] = confirm(label: {!! var_export(__('laravel.initialize_git'), true) !!});
    }

    private function promptForAuth(): void
    {
        $this->answers['workos'] = match (select(
            label: {!! var_export(__('laravel.authentication_provider'), true) !!},
            options: [
                'laravel' => {!! var_export(__('laravel.laravel_auth'), true) !!},
                'workos' => {!! var_export(__('laravel.workos'), true) !!},
            ],
            default: 'laravel',
        )) {
            'laravel' => false,
            'workos' => true,
            default => false,
        };
    }
}

$questionTree = new LaravelConfiguration();
$answers = $questionTree->run();

file_put_contents($argv[1] ?? '/app/output/result.json', json_encode($answers, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
