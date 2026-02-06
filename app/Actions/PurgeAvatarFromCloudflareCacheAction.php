<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class PurgeAvatarFromCloudflareCacheAction
{
    /**
     * Purge a user's avatar from Cloudflare's cache so the updated image is served immediately.
     */
    public function execute(User|string $user): void
    {
        $username = $user instanceof User ? $user->username : $user;

        $zoneId = config('services.cloudflare.zone_id');
        $apiToken = config('services.cloudflare.api_token');

        if (empty($zoneId) || empty($apiToken)) {
            return;
        }

        Http::withToken($apiToken)
            ->post("https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache", [
                'files' => [
                    "https://media.retroachievements.org/UserPic/{$username}.png",
                ],
            ]);
    }
}
