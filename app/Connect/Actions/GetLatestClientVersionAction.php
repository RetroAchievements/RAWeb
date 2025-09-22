<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Models\Emulator;
use Illuminate\Http\Request;

class GetLatestClientVersionAction extends BaseApiAction
{
    protected int $emulatorId;

    public function execute(int $emulatorId): array
    {
        $this->emulatorId = $emulatorId;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['e'])) {
            return $this->missingParameters();
        }

        $this->emulatorId = request()->integer('e', 0);

        return null;
    }

    protected function process(): array
    {
        $emulator = Emulator::find($this->emulatorId);
        if ($emulator === null || !$emulator->active || !$emulator->latestRelease) {
            return $this->resourceNotFound('client');
        }

        // turn relative URLs into full URLs
        $format_url = function (?string $url): ?string {
            return (!$url || str_starts_with($url, 'http')) ? $url : config('app.url') . '/' . $url;
        };

        return [
            'Success' => true,
            'MinimumVersion' => $emulator->minimumSupportedRelease?->version ?? $emulator->latestRelease->version,
            'LatestVersion' => $emulator->latestRelease->version,
            'LatestVersionUrl' => $format_url($emulator->download_url),
            'LatestVersionUrlX64' => $format_url($emulator->download_x64_url),
        ];
    }
}
