<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Models\IntegrationRelease;
use Illuminate\Http\Request;

class GetLatestIntegrationVersionAction extends BaseApiAction
{
    protected int $emulatorId;

    public function execute(): array
    {
        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        return null;
    }

    protected function process(): array
    {
        $minimumRelease = IntegrationRelease::where('minimum', 1)->first();

        // cannot order by semantic version, so assume created_at reflects the release date
        $latestRelease = IntegrationRelease::where('stable', 1)->orderByDesc('created_at')->first();

        return [
            'Success' => true,
            'MinimumVersion' => $minimumRelease->version ?? '0.0',
            'LatestVersion' => $latestRelease->version ?? '0.0',

            // These have historically always been downloaded via http. If/when we add support
            // for non-stable release distribution, we should consider using https if the request
            // was made via https [parse_url(request()->get('url'), PHP_URL_SCHEME) == 'https'];
            'LatestVersionUrl' => 'http://retroachievements.org/bin/RA_Integration.dll',
            'LatestVersionUrlX64' => 'http://retroachievements.org/bin/RA_Integration-x64.dll',
        ];
    }
}
