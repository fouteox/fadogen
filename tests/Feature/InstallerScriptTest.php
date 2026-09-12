<?php

declare(strict_types=1);

use App\Models\Template;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

beforeEach(function () {
    $this->installerPath = sys_get_temp_dir().'/fadogen-installer-'.bin2hex(random_bytes(8));
    File::ensureDirectoryExists($this->installerPath.'/vendor');
});

afterEach(function () {
    File::deleteDirectory($this->installerPath);
});

test('downloaded prompts run independently and preserve the selected authentication provider', function (string $locale) {
    $response = $this->get('/'.$locale.'/prompts/laravel')->assertOk();
    File::put($this->installerPath.'/questions.php', $response->getContent());
    File::put($this->installerPath.'/vendor/autoload.php', <<<'STUB'
<?php
namespace Laravel\Prompts;
function text(...$options) { return 'test-app'; }
function select(...$options) {
    $choices = $options['options'];
    if (isset($choices['8.5'])) { return '8.5'; }
    if (isset($choices['sqlite'])) { return 'sqlite'; }
    if (isset($choices['react'])) { return 'react'; }
    if (isset($choices['workos'])) { return 'workos'; }
    if (isset($choices['horizon'])) { return 'none'; }
    return $choices[0];
}
function multiselect(...$options) { return []; }
function confirm(...$options) { return true; }
STUB);
    $output = $this->installerPath.'/result.json';
    $process = new Process([PHP_BINARY, $this->installerPath.'/questions.php', $output], $this->installerPath);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
    $configuration = json_decode(File::get($output), true, 512, JSON_THROW_ON_ERROR);
    expect($configuration)->toMatchArray([
        'project_name' => 'test-app',
        'php_version' => '8.5',
        'starter_kit' => 'react',
        'workos' => true,
        'queue_type' => null,
        'queue_driver' => null,
        'javascript_package_manager' => 'npm',
    ]);
})->with(['en', 'fr', 'de', 'es']);

test('installer reports failed generation instead of exiting on curl failure', function (int $status, string $message) {
    $response = $this->get('/init/laravel')->assertOk();
    File::put($this->installerPath.'/install.sh', $response->getContent());
    File::ensureDirectoryExists($this->installerPath.'/bin');
    File::put($this->installerPath.'/bin/ddev', "#!/bin/sh\nprintf 'ddev version v1.26.0\\n'\n");
    File::put($this->installerPath.'/bin/docker', <<<'SH'
#!/bin/sh
if [ "$1" = pull ]; then exit 0; fi
for arg in "$@"; do
    case "$arg" in
        *:/app/output) directory=${arg%:/app/output}; printf '{"project_name":"test-app"}' > "$directory/result.json" ;;
    esac
done
SH);
    File::put($this->installerPath.'/bin/curl', <<<'SH'
#!/bin/sh
original_arguments="$*"
status_requested=false
output=''
while [ "$#" -gt 0 ]; do
    case "$1" in
        -w) shift; status_requested=true ;;
        -o) shift; output=$1 ;;
    esac
    shift
done
if [ "$status_requested" = true ]; then
    : > "$output"
    printf '%s' "$FADOGEN_TEST_STATUS"
    case "$original_arguments" in *-f*) exit 22 ;; esac
elif [ -n "$output" ]; then
    : > "$output"
else
    printf 'https://example.invalid/archive'
fi
SH);
    foreach (['ddev', 'docker', 'curl'] as $command) {
        chmod($this->installerPath.'/bin/'.$command, 0755);
    }

    $process = new Process(['sh', $this->installerPath.'/install.sh'], $this->installerPath, [
        'PATH' => $this->installerPath.'/bin:'.getenv('PATH'),
        'FADOGEN_TEST_STATUS' => (string) $status,
        'LANG' => 'en_US.UTF-8',
    ]);
    $process->run();

    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getOutput().$process->getErrorOutput())->toContain($message);
})->with([[422, 'Template generation failed'], [404, 'Template not found']]);

test('installer treats historical project names as literal shell data', function () {
    $template = Template::factory()->createQuietly([
        'data' => ['project_name' => '$(touch injected)'],
    ]);
    $script = $this->get(route('init.template', $template))->assertOk()->getContent();
    File::put($this->installerPath.'/install.sh', $script);
    File::ensureDirectoryExists($this->installerPath.'/bin');
    File::put($this->installerPath.'/bin/ddev', "#!/bin/sh\nprintf 'ddev version v1.26.0\\n'\n");
    File::put($this->installerPath.'/bin/curl', "#!/bin/sh\nexit 22\n");
    foreach (['ddev', 'curl'] as $command) {
        chmod($this->installerPath.'/bin/'.$command, 0755);
    }

    $process = new Process(['sh', $this->installerPath.'/install.sh'], $this->installerPath, [
        'PATH' => $this->installerPath.'/bin:'.getenv('PATH'),
    ]);
    $process->run();

    expect($process->isSuccessful())->toBeFalse()
        ->and(File::exists($this->installerPath.'/injected'))->toBeFalse()
        ->and($process->getOutput().$process->getErrorOutput())->toContain('Failed to download template');
});
