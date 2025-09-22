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

        return [
            'Success' => true,
            'MinimumVersion' => $emulator->minimumSupportedRelease?->version ?? $emulator->latestRelease->version,
            'LatestVersion' => $emulator->latestRelease->version,
            'LatestVersionUrl' => $this->ensureFullUrl($emulator->download_url),
            'LatestVersionUrlX64' => $this->ensureFullUrl($emulator->download_x64_url),
        ];
    }

    private function ensureFullUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        if (str_starts_with($url, 'http')) {
            return $url;
        }

        return config('app.url') . '/' . $url;
    }
}
