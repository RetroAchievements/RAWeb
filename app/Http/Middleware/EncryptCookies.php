<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        'scheme', // dark/light mode
        'theme', // color scheme
        'logo',
        'active_players_search',
        'authenticated',
        'datatable_view_preference_all_games',
        'datatable_view_preference_generic_games',
        'datatable_view_preference_hub_games',
        'datatable_view_preference_playlist',
        'datatable_view_preference_suggest',
        'datatable_view_preference_system_games',
        'hide_unlocked_achievements_games',
        'prefers_hidden_user_completed_sets',
        'prefers_hidden_user_profile_stats',
        'prefers_seeing_saved_hidden_rows_when_reordering',
        'progression_status_widths_preference',
    ];
}
