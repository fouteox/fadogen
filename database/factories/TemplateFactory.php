<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TemplateStatusEnum;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

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
                'project_name' => $this->faker->words(3, true),
                'server_contact' => $this->faker->email(),
                'needs_traefik' => $this->faker->boolean(),
                'network' => $this->faker->optional()->word(),
                'php_version' => $this->faker->randomElement(['8.2', '8.3', '8.4']),
                'php_extensions' => $this->faker->randomElements(['pdo', 'mysql', 'redis', 'gd'], 2),
                'database' => $this->faker->randomElement(['mariadb', 'mysql', 'postgresql']),
                'starter_kit' => 'none',
                'mono_repo' => 'none',
                'testing_framework' => 'pest',
                'queue' => 'none',
                'features' => [],
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
