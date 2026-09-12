<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TemplateStatusEnum;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Template> */
final class TemplateFactory extends Factory
{
    protected $model = Template::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'data' => [
                'project_name' => $this->faker->unique()->slug(3),
                'php_version' => '8.5',
                'database' => 'sqlite',
                'starter_kit' => 'none',
                'custom_starter_kit' => null,
                'livewire_volt' => false,
                'workos' => false,
                'testing_framework' => 'pest',
                'queue_type' => null,
                'queue_driver' => null,
                'features' => [],
                'javascript_package_manager' => 'npm',
                'initialize_git' => true,
            ],
            'status' => TemplateStatusEnum::Pending,
        ];
    }

    public function completed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => TemplateStatusEnum::Completed,
        ]);
    }

    public function failed(): self
    {
        return $this->state(fn (array $attributes) => [
            'status' => TemplateStatusEnum::Failed,
        ]);
    }
}
