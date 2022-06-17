<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IntegrationReleaseRequest extends FormRequest
{
    public function rules(Request $request): array
    {
        return [
            'version' => [
                'required',
                'string',
                Rule::unique('integration_releases', 'version')
                    ->ignore($request->route('release')),
            ],
            'stable' => 'boolean',
            'minimum' => 'boolean',
            'notes' => 'nullable|string',
            'build_x86' => 'nullable|file|max:4096|mimetypes:application/x-dosexec',
            'build_x64' => 'nullable|file|max:4096|mimetypes:application/x-dosexec',
        ];
    }
}
