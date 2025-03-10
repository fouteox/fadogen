<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TemplateCreatedEvent;
use App\Jobs\ProcessTemplateJob;

final class ProcessTemplateListener
{
    public function __construct() {}

    public function handle(TemplateCreatedEvent $event): void
    {
        ProcessTemplateJob::dispatch($event->template);
    }
}
