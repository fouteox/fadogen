<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TemplateStatusEnum;
use App\Events\TemplateCreatedEvent;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

final class Template extends Model
{
    use HasFactory, HasUlids;

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
