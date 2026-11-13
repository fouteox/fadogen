<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Translation\FileLoader;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class I18NextTranslationsLoader
{
    /**
     * Groupes PHP dont l'interface lit des clés, en plus des fichiers JSON.
     * Les autres (validation, mots de passe, statuts HTTP…) ne servent qu'au
     * serveur : les envoyer alourdissait chaque page de plus de 700 clés.
     */
    public const array CLIENT_GROUPS = ['laravel'];

    /**
     * Les fichiers de langue ne changent pas pendant la vie du processus :
     * le catalogue d'une locale est calculé une fois par worker.
     *
     * @var array<string, array<string, string>>
     */
    private array $catalogues = [];

    public function __construct(
        private readonly Filesystem $fs,
        private readonly FileLoader $loader,
        private readonly string $langPath,
    ) {}

    /**
     * @return array<string, string>
     */
    public function loadTranslations(string $locale): array
    {
        return $this->catalogues[$locale] ??= $this->buildCatalogue($locale);
    }

    /**
     * @return array<string, string>
     */
    private function buildCatalogue(string $locale): array
    {
        $fallbackLocale = config('app.fallback_locale', 'en');
        $useLocale = $this->localeExists($locale) ? $locale : $fallbackLocale;

        if (! $this->localeExists($useLocale)) {
            return [];
        }

        $translations = $this->loader->load($useLocale, '*', '*');
        $groups = array_intersect(
            array_map(
                fn (SplFileInfo $file) => $file->getBasename('.php'),
                $this->fs->files($this->langPath.'/'.$useLocale),
            ),
            self::CLIENT_GROUPS,
        );

        foreach ($groups as $group) {
            $nonPrefixedGroupTranslations = $this->loader->load($useLocale, $group);
            $groupTranslations = [];
            foreach ($nonPrefixedGroupTranslations as $key => $translation) {
                $groupTranslations[$group.'.'.$key] = $translation;
            }
            $translations = array_merge($translations, $groupTranslations);
        }

        return $this->prepare($translations);
    }

    private function localeExists(string $locale): bool
    {
        return $this->fs->exists($this->langPath.'/'.$locale);
    }

    private function prepare(array $translations): array
    {
        $i18nTranslations = [];
        $keys = array_keys($translations);

        for ($i = 0; $i < count($keys); $i++) {
            $laravelKey = $keys[$i];
            $i18nKey = preg_replace("/:(\w+)/", '{{$1}}', is_int($laravelKey) ? (string) $laravelKey : $laravelKey);
            $laravelValue = $translations[$laravelKey];

            if (is_array($laravelValue)) {
                $i18nTranslations[$i18nKey] = $this->prepare($laravelValue);
            } else {
                $translationWithReplacedVariableSyntax = preg_replace("/:(\w+)/", '{{$1}}', $laravelValue);
                // handle pluralisation
                if (Str::contains($translationWithReplacedVariableSyntax, '|')) {
                    [$one, $other] = explode('|', $translationWithReplacedVariableSyntax);
                    $i18nTranslations[$i18nKey.'_one'] = $one;
                    $i18nTranslations[$i18nKey.'_other'] = $other;
                } else {
                    $i18nTranslations[$i18nKey] = $translationWithReplacedVariableSyntax;
                }
            }
        }

        return $this->flatten($i18nTranslations);
    }

    private function flatten($translations): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($translations));
        $flattened = [];

        foreach ($iterator as $leafValue) {
            $keys = [];

            foreach (range(0, $iterator->getDepth()) as $depth) {
                $keys[] = $iterator->getSubIterator($depth)->key();
            }

            $flattened[implode('.', $keys)] = $leafValue;
        }

        return $flattened;
    }
}
