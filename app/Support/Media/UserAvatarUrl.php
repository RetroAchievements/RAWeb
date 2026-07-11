<?php

declare(strict_types=1);

namespace App\Support\Media;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class UserAvatarUrl
{
    public static function canonical(string $username): string
    {
        return media_asset("UserPic/{$username}.png");
    }

    public static function versioned(string $username, CarbonInterface|string|null $avatarUpdatedAt): string
    {
        return self::canonical($username) . '?v=' . self::version($avatarUpdatedAt);
    }

    public static function fromRecord(string $username, array $attributes): string
    {
        if (!array_key_exists('avatar_updated_at', $attributes)) {
            return self::canonical($username);
        }

        return self::versioned($username, $attributes['avatar_updated_at']);
    }

    /**
     * Build every avatar URL variant that may be live in Cloudflare's cache.
     *
     * @return string[]
     */
    public static function purgeVariants(
        string $username,
        CarbonInterface|string|null $previousAvatarUpdatedAt,
        CarbonInterface|string|null $currentAvatarUpdatedAt,
    ): array {
        $baseUrl = rtrim((string) config('filesystems.disks.media.url'), '/') . "/UserPic/{$username}.png";

        return array_values(array_unique(array_filter([
            $baseUrl,
            "{$baseUrl}?v=0",
            $previousAvatarUpdatedAt !== null ? "{$baseUrl}?v=" . self::version($previousAvatarUpdatedAt) : null,
            $currentAvatarUpdatedAt !== null ? "{$baseUrl}?v=" . self::version($currentAvatarUpdatedAt) : null,
        ])));
    }

    private static function version(CarbonInterface|string|null $avatarUpdatedAt): int
    {
        if ($avatarUpdatedAt === null) {
            return 0;
        }

        if (is_string($avatarUpdatedAt)) {
            return Carbon::parse($avatarUpdatedAt)->unix();
        }

        return $avatarUpdatedAt->unix();
    }
}
