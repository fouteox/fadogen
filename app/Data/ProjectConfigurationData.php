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
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MergeValidationRules;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\ValidationContext;

#[MergeValidationRules]
final class ProjectConfigurationData extends Data
{
    public const string PROJECT_NAME_PATTERN = '/\A(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)*[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\z/';

    public const string PACKAGE_NAME_PATTERN = '/\A[a-z0-9]+(?:[_.-][a-z0-9]+)*\/[a-z0-9]+(?:[_.-][a-z0-9]+)*\z/';

    public function __construct(
        #[Max(255)]
        #[Regex(self::PROJECT_NAME_PATTERN)]
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

        /** @var list<FeaturesEnum> */
        #[Present]
        public array $features,

        public JavascriptPackageManagerEnum $javascript_package_manager,

        public bool $initialize_git,
    ) {}

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public static function prepareForPipeline(array $properties): array
    {
        $properties['features'] ??= [];

        return $properties;
    }

    /** @return array<string, list<mixed>> */
    public static function rules(ValidationContext $context): array
    {
        $payload = is_array($context->payload) ? $context->payload : [];
        $starterKit = $payload['starter_kit'] ?? null;

        if ($starterKit instanceof StarterKitEnum) {
            $starterKit = $starterKit->value;
        }

        $usesHorizon = in_array($payload['queue_type'] ?? null, [QueueTypeEnum::Horizon, QueueTypeEnum::Horizon->value], true);
        $usesWorkos = in_array($payload['workos'] ?? null, [true, 1, '1'], true);

        return [
            'features' => ['list'],
            'features.*' => [Rule::enum(FeaturesEnum::class), 'distinct'],
            'custom_starter_kit' => [
                'bail',
                Rule::when(
                    $starterKit === StarterKitEnum::Custom->value,
                    [
                        'required',
                        'string',
                        'max:255',
                        'regex:'.self::PACKAGE_NAME_PATTERN,
                        function (string $attribute, string $value, Closure $fail): void {
                            try {
                                $response = Http::connectTimeout(3)->timeout(5)
                                    ->get("https://packagist.org/packages/$value.json");
                            } catch (ConnectionException) {
                                $fail('Impossible de vérifier le package sur Packagist.org. Veuillez réessayer.');

                                return;
                            }

                            if (! $response->successful() || $response->json('package.name') !== $value) {
                                $fail("Le package '$value' n'existe pas sur Packagist.org.");
                            }
                        },
                    ],
                    ['nullable', 'string', 'max:255']
                ),
            ],
            'queue_driver' => [
                Rule::requiredIf($usesHorizon),
                Rule::when(
                    $usesHorizon,
                    [Rule::in([QueueDriverEnum::Redis->value, QueueDriverEnum::Valkey->value])],
                ),
            ],
            'livewire_volt' => [
                Rule::when($starterKit !== StarterKitEnum::Livewire->value || $usesWorkos, ['not_in:1']),
            ],
            'workos' => [
                Rule::when(in_array($starterKit, [StarterKitEnum::None->value, StarterKitEnum::Custom->value], true), ['not_in:1']),
            ],
        ];
    }
}
