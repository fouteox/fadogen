<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DetectDependenciesRequest;
use App\Services\DependencyDetector;
use Dotenv\Exception\InvalidFileException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use UnexpectedValueException;

final class DependenciesDetectionController extends Controller
{
    public function detect(DetectDependenciesRequest $request, DependencyDetector $detector): JsonResponse
    {
        try {
            return response()->json(['detected' => $detector->detect($request->string('package')->toString())]);
        } catch (ConnectionException|RequestException) {
            return response()->json(['message' => 'Le service de détection est temporairement indisponible. Réessayez plus tard.'], 502);
        } catch (InvalidFileException|UnexpectedValueException) {
            return response()->json(['message' => 'Les métadonnées du package sont invalides.'], 502);
        }
    }
}
