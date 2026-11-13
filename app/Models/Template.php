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
    use HasFactory, HasUlids, Prunable;

    /**
     * Durée de vie d'un modèle généré : passé ce délai, la ligne et son
     * archive sont supprimées par `model:prune`.
     */
    public const int RETENTION_DAYS = 7;

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

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

    public function getDownloadUrl(): string
    {
        return URL::signedRoute(
            'templates.download',
            ['template' => $this->id]
        );
    }

    /**
     * Supprime l'archive avant la ligne, pour qu'un échec de stockage laisse
     * la ligne en place et soit retenté au passage suivant.
     */
    protected function pruning(): void
    {
        $archive = $this->id.'.tar';

        // Le disque ne lève pas d'exception : un échec renvoie false.
        throw_unless(
            Storage::disk('generated-templates')->delete($archive),
            RuntimeException::class,
            "Archive {$archive} non supprimée",
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
