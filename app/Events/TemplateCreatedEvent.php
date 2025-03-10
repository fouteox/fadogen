<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Template;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TemplateCreatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Template $template
    ) {}
}
