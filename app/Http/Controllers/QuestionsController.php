<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

final class QuestionsController extends Controller
{
    /**
     * @throws FileNotFoundException
     */
    public function __invoke(string $template = 'laravel'): Response
    {
        // Lit le contenu du fichier PHP
        $content = File::get(resource_path("prompts/$template.php"));

        // Remplace les traductions
        $translatedContent = preg_replace_callback(
            '/__\([\'"]([^\'"]+)[\'"](?:(,\s*)(.*?))?\)/',
            function ($matches) {
                $key = $matches[1];
                $translation = trans($key);

                // Si la traduction a des paramètres
                if (isset($matches[2])) {
                    // On retire les ':' des marqueurs de la traduction
                    $translation = preg_replace('/:(\w+)/', '$1', $translation);

                    return "strtr('".$translation."'".$matches[2].$matches[3].')';
                }

                // Sinon, on retourne juste la traduction
                return var_export($translation, true);
            },
            $content
        );

        return response($translatedContent)
            ->header('Content-Type', 'text/x-php');
    }
}
