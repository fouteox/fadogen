<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateStatusEnum;
use App\Events\TemplateCreatedEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;

final class Template extends Model
{
    /** @use HasFactory<\Database\Factories\TemplateFactory> */
    use HasFactory;

    use HasUlids, Prunable;

    protected $fillable = [
        'data',
        'status',
    ];

    /**
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => TemplateCreatedEvent::class,
    ];

    public function getDownloadUrl(): string
    {
        return URL::signedRoute(
            'templates.download',
            ['template' => $this->id]
        );
    }

    /** @return Builder<static> */
    public function prunable(): Builder
    {
        return self::query()
            ->whereIn('status', [TemplateStatusEnum::Completed, TemplateStatusEnum::Downloaded, TemplateStatusEnum::Failed])
            ->where('created_at', '<=', now()->subDays(max(1, config()->integer('app.template_retention_days'))));
    }

    protected function pruning(): void
    {
        if (! Storage::disk('generated-templates')->delete($this->id.'.tar')) {
            throw new RuntimeException('Unable to delete the template archive.');
        }
    }

    /**
     * @return array{
     *   data: 'array',
     *   status: 'App\Enums\TemplateStatusEnum',
     * }
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'status' => TemplateStatusEnum::class,
        ];
    }
}
