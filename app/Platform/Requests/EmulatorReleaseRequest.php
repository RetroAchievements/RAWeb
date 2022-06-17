<?php

declare(strict_types=1);

namespace App\Platform\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmulatorReleaseRequest extends FormRequest
{
    public function rules(Request $request): array
    {
        return [
            'version' => [
                'required',
                'string',
                Rule::unique('emulator_releases')
                    ->where(function ($query) use ($request) {
                        $emulator = $request->emulator ?? $request->release->emulator;

                        return $query->where('emulator_id', $emulator->id)
                            ->where('version', $request->version);
                    })
                    ->ignore($request->route('release')),
            ],
            'stable' => 'boolean',
            'minimum' => 'boolean',
            'notes' => 'nullable|string',
            'build_x86' => 'nullable|file|max:16384|mimetypes:application/zip',
            'build_x64' => 'nullable|file|max:16384|mimetypes:application/zip',
        ];
    }
}
