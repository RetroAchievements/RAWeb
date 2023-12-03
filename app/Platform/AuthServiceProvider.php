<?php

declare(strict_types=1);

namespace App\Platform;

use App\Platform\Models\Achievement;
use App\Platform\Models\Badge;
use App\Platform\Models\Emulator;
use App\Platform\Models\EmulatorRelease;
use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\GameHashSet;
use App\Platform\Models\IntegrationRelease;
use App\Platform\Models\Leaderboard;
use App\Platform\Models\LeaderboardEntry;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerBadge;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\System;
use App\Platform\Policies\AchievementPolicy;
use App\Platform\Policies\BadgePolicy;
use App\Platform\Policies\EmulatorPolicy;
use App\Platform\Policies\EmulatorReleasePolicy;
use App\Platform\Policies\GameHashPolicy;
use App\Platform\Policies\GameHashSetPolicy;
use App\Platform\Policies\GamePolicy;
use App\Platform\Policies\IntegrationReleasePolicy;
use App\Platform\Policies\LeaderboardEntryPolicy;
use App\Platform\Policies\LeaderboardPolicy;
use App\Platform\Policies\PlayerAchievementPolicy;
use App\Platform\Policies\PlayerBadgePolicy;
use App\Platform\Policies\PlayerGamePolicy;
use App\Platform\Policies\SystemPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        /*
         * Release Management Policies
         */
        EmulatorRelease::class => EmulatorReleasePolicy::class,
        IntegrationRelease::class => IntegrationReleasePolicy::class,

        /*
         * Server Policies
         */
        Achievement::class => AchievementPolicy::class,
        Badge::class => BadgePolicy::class,
        Emulator::class => EmulatorPolicy::class,
        Game::class => GamePolicy::class,
        GameHash::class => GameHashPolicy::class,
        GameHashSet::class => GameHashSetPolicy::class,
        Leaderboard::class => LeaderboardPolicy::class,
        LeaderboardEntry::class => LeaderboardEntryPolicy::class,
        System::class => SystemPolicy::class,
        PlayerAchievement::class => PlayerAchievementPolicy::class,
        PlayerBadge::class => PlayerBadgePolicy::class,
        PlayerGame::class => PlayerGamePolicy::class,
    ];

    public function boot(): void
    {
        /*
         * providers
         */
        // Auth::provider('eloquent-md5', function ($app, array $config) {
        //     return new LegacyUserProvider(new BcryptHasher(), User::class);
        // });
        //
        // Auth::extend('api-token', function ($app, $name, array $config) {
        //     return new TokenGuard(Auth::createUserProvider($config['provider']), $app->request, 'z', 'api_token');
        // });
        //
        // Auth::extend('connect-token', function ($app, $name, array $config) {
        //     return new TokenGuard(Auth::createUserProvider($config['provider']), $app->request, 't', 'connect_token');
        // });
    }
}
