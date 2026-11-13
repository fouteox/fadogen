<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\DatabaseEnum;
use App\Enums\FeaturesEnum;
use App\Enums\JavascriptPackageManagerEnum;
use App\Enums\PhpVersionEnum;
use App\Enums\QueueDriverEnum;
use App\Enums\QueueTypeEnum;
use App\Enums\StarterKitEnum;
use App\Enums\TestingFrameworkEnum;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

#[MergeValidationRules]
final class ProjectConfigurationData extends Data
{
    /**
     * Seuls ces caractères sont admis dans un nom de projet : il est recopié
     * tel quel dans le script d'installation exécuté sur la machine cliente.
     */
    public const string PROJECT_NAME_PATTERN = '/\\A[a-zA-Z0-9_-]+\\z/';

    /**
     * Forme « vendor/package » des noms Composer, elle aussi recopiée dans le
     * script d'installation.
     */
    public const string COMPOSER_PACKAGE_PATTERN = '/\\A[a-z0-9]([_.-]?[a-z0-9]+)*\\/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*\\z/i';

    public function __construct(
        #[Max(255)]
        public string $project_name,

        public PhpVersionEnum $php_version,

        public DatabaseEnum $database,

        public StarterKitEnum $starter_kit,

        public ?string $custom_starter_kit,

        public ?bool $livewire_volt,

        public ?bool $workos,

        public TestingFrameworkEnum $testing_framework,

        public ?QueueTypeEnum $queue_type,

        public ?QueueDriverEnum $queue_driver,

        /** @var array<int, FeaturesEnum> */
        public ?array $features,

        public JavascriptPackageManagerEnum $javascript_package_manager,

        public bool $initialize_git,
    ) {}

    /**
     * Indique si une configuration déjà enregistrée peut être recopiée sans
     * risque dans un script shell.
     *
     * @param  array<string, mixed>  $configuration
     */
    public static function isSafeForShell(array $configuration): bool
    {
        $projectName = $configuration['project_name'] ?? null;
        $customStarterKit = $configuration['custom_starter_kit'] ?? null;

        if (! is_string($projectName) || preg_match(self::PROJECT_NAME_PATTERN, $projectName) !== 1) {
            return false;
        }

        if ($customStarterKit === null || $customStarterKit === '') {
            return true;
        }

        return is_string($customStarterKit) && preg_match(self::COMPOSER_PACKAGE_PATTERN, $customStarterKit) === 1;
    }

    public static function rules(ValidationContext $context): array
    {
        return [
            'project_name' => ['regex:'.self::PROJECT_NAME_PATTERN],
            'features.*' => [Rule::enum(FeaturesEnum::class)],
            'custom_starter_kit' => [
                Rule::when(
                    request()->input('starter_kit') === StarterKitEnum::Custom->value,
                    ['required', 'string', 'max:255', 'bail', 'regex:'.self::COMPOSER_PACKAGE_PATTERN, function ($attribute, $value, $fail) {
                        if (! empty($value)) {
                            try {
                                $response = Http::connectTimeout(2)->timeout(5)->get("https://packagist.org/packages/$value.json");
                                if (! $response->successful()) {
                                    $fail("Le package '$value' n'existe pas sur Packagist.org.");
                                }
                            } catch (Exception $e) {
                                $fail("Erreur lors de la vérification du package sur Packagist.org: {$e->getMessage()}");
                            }
                        }
                    }],
                    ['nullable', 'string', 'max:255', 'regex:'.self::COMPOSER_PACKAGE_PATTERN]
                ),
            ],
            'queue_driver' => [
                Rule::when(
                    request()->input('queue_type') === QueueTypeEnum::Horizon->value,
                    fn () => Rule::in([QueueDriverEnum::Redis->value, QueueDriverEnum::Valkey->value]),
                    fn () => Rule::in(array_map(fn ($enum) => $enum->value, QueueDriverEnum::cases()))
                ),
            ],
            'livewire_volt' => [
                function ($attribute, $value, $fail) {
                    $starter_kit = request()->input('starter_kit');
                    $workos = request()->input('workos');

                    if ($value === true && ($starter_kit !== StarterKitEnum::Livewire->value || $workos === true)) {
                        $fail("Le champ $attribute ne peut être activé que lorsque le starter kit est défini sur 'livewire' et que workos est désactivé.");
                    }
                },
            ],
            'workos' => [
                function ($attribute, $value, $fail) {
                    $starter_kit = request()->input('starter_kit');
                    if ($value === true && ($starter_kit === StarterKitEnum::None->value || $starter_kit === StarterKitEnum::Custom->value)) {
                        $fail("Le champ $attribute ne peut pas être activé lorsque le starter kit est défini sur 'none' ou 'custom'.");
                    }
                },
            ],
        ];
    }
}
