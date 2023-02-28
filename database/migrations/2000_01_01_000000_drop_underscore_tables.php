<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        // Cleanup prefixed tables that are not used anymore due to the schema merge

        Schema::dropIfExists('_game_achievement_sets');
        Schema::dropIfExists('_user_achievement_sets');
        Schema::dropIfExists('_achievement_authors');
        Schema::dropIfExists('_achievement_set_achievements');
        Schema::dropIfExists('_achievement_set_authors');
        Schema::dropIfExists('_achievement_set_versions');
        Schema::dropIfExists('_achievement_sets');
        Schema::dropIfExists('_player_achievements');
        Schema::dropIfExists('_achievements');
        Schema::dropIfExists('_activity_log');
        Schema::dropIfExists('_auth_model_permissions');
        Schema::dropIfExists('_auth_model_roles');
        Schema::dropIfExists('_auth_role_permissions');
        Schema::dropIfExists('_auth_permissions');
        Schema::dropIfExists('_auth_roles');
        Schema::dropIfExists('_badge_set_badges');
        Schema::dropIfExists('_badge_sets');
        Schema::dropIfExists('_user_badge_stages');
        Schema::dropIfExists('_user_badges');
        Schema::dropIfExists('_badge_stages');
        Schema::dropIfExists('_badges');
        Schema::dropIfExists('_comments');
        Schema::dropIfExists('_emulator_releases');
        Schema::dropIfExists('_system_emulators');
        Schema::dropIfExists('_emulators');
        Schema::dropIfExists('_event_entries');
        Schema::dropIfExists('_event_dates');
        Schema::dropIfExists('_events');
        Schema::dropIfExists('_failed_jobs');
        Schema::dropIfExists('_forum_topics');
        Schema::dropIfExists('_forums');
        Schema::dropIfExists('_forum_categories');
        Schema::dropIfExists('_game_hash_set_hashes');
        Schema::dropIfExists('_memory_notes');
        Schema::dropIfExists('_game_hash_sets');
        Schema::dropIfExists('_tickets');
        Schema::dropIfExists('_player_sessions');
        Schema::dropIfExists('_game_hashes');
        Schema::dropIfExists('_game_relations');
        Schema::dropIfExists('_game_set_games');
        Schema::dropIfExists('_game_sets');
        Schema::dropIfExists('_leaderboard_entries');
        Schema::dropIfExists('_leaderboards');
        Schema::dropIfExists('_player_games');
        Schema::dropIfExists('_games');
        Schema::dropIfExists('_integration_releases');
        Schema::dropIfExists('_jobs');
        Schema::dropIfExists('_media');
        Schema::dropIfExists('_messages');
        Schema::dropIfExists('_migrations');
        Schema::dropIfExists('_news');
        Schema::dropIfExists('_notifications');
        Schema::dropIfExists('_oauth_access_tokens');
        Schema::dropIfExists('_oauth_auth_codes');
        Schema::dropIfExists('_oauth_clients');
        Schema::dropIfExists('_oauth_personal_access_clients');
        Schema::dropIfExists('_oauth_refresh_tokens');
        Schema::dropIfExists('_password_resets');
        Schema::dropIfExists('_personal_access_tokens');
        Schema::dropIfExists('_ratings');
        Schema::dropIfExists('_sessions');
        Schema::dropIfExists('_sync_status');
        Schema::dropIfExists('_systems');
        Schema::dropIfExists('_taggables');
        Schema::dropIfExists('_tags');
        Schema::dropIfExists('_user_triggers');
        Schema::dropIfExists('_triggers');
        Schema::dropIfExists('_user_activity_log');
        Schema::dropIfExists('_user_connections');
        Schema::dropIfExists('_user_relations');
        Schema::dropIfExists('_user_usernames');
        Schema::dropIfExists('_votes');
        Schema::dropIfExists('_users');
        Schema::dropIfExists('_websockets_statistics_entries');
    }

    public function down(): void
    {
        // nope. prefix is gone for good
    }
};
