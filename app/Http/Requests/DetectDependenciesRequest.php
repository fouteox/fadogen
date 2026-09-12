<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Data\ProjectConfigurationData;
use Illuminate\Foundation\Http\FormRequest;

final class DetectDependenciesRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'package' => ['required', 'string', 'max:255', 'regex:'.ProjectConfigurationData::PACKAGE_NAME_PATTERN],
        ];
    }
}
