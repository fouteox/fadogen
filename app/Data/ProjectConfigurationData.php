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

    public static function rules(ValidationContext $context): array
    {
        return [
            'features.*' => [Rule::enum(FeaturesEnum::class)],
            'custom_starter_kit' => [
                Rule::when(
                    request()->input('starter_kit') === StarterKitEnum::Custom->value,
                    ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                        if (!empty($value)) {
                            try {
                                $response = Http::get("https://packagist.org/packages/$value.json");
                                if (!$response->successful()) {
                                    $fail("Le package '$value' n'existe pas sur Packagist.org.");
                                }
                            } catch (Exception $e) {
                                $fail("Erreur lors de la vérification du package sur Packagist.org: {$e->getMessage()}");
                            }
                        }
                    }],
                    ['nullable', 'string', 'max:255']
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
