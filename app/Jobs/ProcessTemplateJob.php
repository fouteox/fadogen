<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Data\ProjectConfigurationData;
use App\Enums\TemplateStatusEnum;
use App\Models\Template;
use App\Services\TemplateCommandGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\UnableToWriteFile;
use PharData;
use RuntimeException;
use Throwable;

final class ProcessTemplateJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Template $template) {}

    public function handle(TemplateCommandGenerator $generator): void
    {
        try {
            $commands = $generator->generate(ProjectConfigurationData::from($this->template->data));
            $this->createArchive($commands);
            $this->template->update(['status' => TemplateStatusEnum::Completed]);
        } catch (Throwable $exception) {
            $this->template->update(['status' => TemplateStatusEnum::Failed]);

            throw $exception;
        }
    }

    /** @param list<string> $commands */
    private function createArchive(array $commands): void
    {
        $temporaryDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fadogen-'.Str::uuid();

        try {
            if (! File::makeDirectory($temporaryDirectory, 0700, true)) {
                throw new RuntimeException('Unable to create the template workspace.');
            }

            $archivePath = $temporaryDirectory.DIRECTORY_SEPARATOR.'template.tar';
            $archive = new PharData($archivePath);
            $archive->addFromString('commands.sh', implode(PHP_EOL, $commands).PHP_EOL);

            $stream = fopen($archivePath, 'rb');

            if ($stream === false) {
                throw new RuntimeException('Unable to read the generated archive.');
            }

            try {
                $path = $this->template->id.'.tar';

                if (! Storage::disk('generated-templates')->put($path, $stream)) {
                    throw UnableToWriteFile::atLocation($path);
                }
            } finally {
                fclose($stream);
            }
        } finally {
            unset($archive);
            File::deleteDirectory($temporaryDirectory);
        }
    }
}
