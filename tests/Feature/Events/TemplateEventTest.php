<?php

declare(strict_types=1);

use App\Events\TemplateCreatedEvent;
use App\Jobs\ProcessTemplateJob;
use App\Models\Template;
use Illuminate\Support\Facades\Event;

test('la création d\'un template déclenche TemplateCreatedEvent', function () {
    Event::fake();

    $template = Template::factory()->create();

    Event::assertDispatched(TemplateCreatedEvent::class, function ($event) use ($template) {
        return $event->template->id === $template->id;
    });
});

test('TemplateCreatedEvent déclenche ProcessTemplateJob', function () {
    Template::factory()->create();

    Queue::assertPushed(ProcessTemplateJob::class);
});
