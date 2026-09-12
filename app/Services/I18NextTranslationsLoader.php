<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Translation\FileLoader;
use RuntimeException;

final readonly class I18NextTranslationsLoader
{
    public function __construct(
        private Filesystem $fs,
        private FileLoader $loader,
        private string $langPath,
    ) {}

    /** @return array<string, string> */
    public function loadTranslations(string $locale): array
    {
        $useLocale = $this->localeExists($locale) ? $locale : config()->string('app.fallback_locale');

        if (! $this->localeExists($useLocale)) {
            return [];
        }

        $translations = $this->loader->load($useLocale, '*', '*');

        foreach ($this->fs->files($this->langPath.'/'.$useLocale) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group = $file->getBasename('.php');
            foreach ($this->loader->load($useLocale, $group) as $key => $translation) {
                $translations[$group.'.'.$key] = $translation;
            }
        }

        $prepared = [];

        foreach (Arr::dot($translations) as $key => $translation) {
            if (! is_string($translation)) {
                continue;
            }

            $key = preg_replace('/:(\w+)/', '{{$1}}', (string) $key) ?? throw new RuntimeException(preg_last_error_msg());
            $value = preg_replace('/:(\w+)/', '{{$1}}', $translation) ?? throw new RuntimeException(preg_last_error_msg());

            if (Str::contains($value, '|')) {
                [$one, $other] = explode('|', $value);
                $prepared[$key.'_one'] = $one;
                $prepared[$key.'_other'] = $other;
            } else {
                $prepared[$key] = $value;
            }
        }

        return $prepared;
    }

    private function localeExists(string $locale): bool
    {
        return $this->fs->isDirectory($this->langPath.'/'.$locale);
    }
}
