<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Drop foreign key constraint on UserAccounts that references columns we're renaming.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('UserAccounts', function (Blueprint $table) {
                $table->dropForeign('useraccounts_visible_role_id_foreign');
            });
        }

        // Rename columns.
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->renameColumn('ID', 'id');
            $table->renameColumn('User', 'username');
            $table->renameColumn('Password', 'password');
            $table->renameColumn('SaltedPass', 'legacy_salted_password');
            $table->renameColumn('EmailAddress', 'email');
            $table->renameColumn('RAPoints', 'points_hardcore');
            $table->renameColumn('RASoftcorePoints', 'points');
            $table->renameColumn('appToken', 'connect_token');
            $table->renameColumn('appTokenExpiry', 'connect_token_expires_at');
            $table->renameColumn('websitePrefs', 'preferences_bitfield');
            $table->renameColumn('LastLogin', 'last_activity_at');
            $table->renameColumn('Motto', 'motto');
            $table->renameColumn('ContribCount', 'yield_unlocks');
            $table->renameColumn('ContribYield', 'yield_points');
            $table->renameColumn('APIKey', 'web_api_key');
            $table->renameColumn('APIUses', 'web_api_calls');
            $table->renameColumn('LastGameID', 'rich_presence_game_id');
            $table->renameColumn('RichPresenceMsg', 'rich_presence');
            $table->renameColumn('RichPresenceMsgDate', 'rich_presence_updated_at');
            $table->renameColumn('UnreadMessageCount', 'unread_messages');
            $table->renameColumn('TrueRAPoints', 'points_weighted');
            $table->renameColumn('UserWallActive', 'is_user_wall_active');
            $table->renameColumn('Created', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
            $table->renameColumn('DeleteRequested', 'delete_requested_at');
            $table->renameColumn('Deleted', 'deleted_at');
            $table->renameColumn('email_backup', 'email_original');
        });

        // Rename the table.
        Schema::rename('UserAccounts', 'users');

        // Recreate foreign key constraint with new column names.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('visible_role_id', 'users_visible_role_id_foreign')
                    ->references('id')
                    ->on('auth_roles')
                    ->onDelete('set null');
            });
        }

        // Rename indexes to match the new table and column names.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->renameIndex('useraccounts_display_name_unique', 'users_display_name_unique');
                $table->renameIndex('useraccounts_ulid_unique', 'users_ulid_unique');
                $table->renameIndex('users_apikey_index', 'users_web_api_key_index');
                $table->renameIndex('users_apptoken_index', 'users_connect_token_index');
                $table->renameIndex('useraccounts_lastlogin_deleted_index', 'users_last_activity_at_deleted_at_index');
                $table->renameIndex('useraccounts_visible_role_id_index', 'users_visible_role_id_index');
            });
        }

        // Reorder columns into logical groups.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement(<<<SQL
                ALTER TABLE users
                    -- Identity
                    MODIFY COLUMN `ulid` char(26) DEFAULT NULL AFTER `id`,
                    MODIFY COLUMN `username` varchar(32) NOT NULL AFTER `ulid`,
                    MODIFY COLUMN `display_name` varchar(255) DEFAULT NULL AFTER `username`,

                    -- Authentication
                    MODIFY COLUMN `password` varchar(255) DEFAULT NULL AFTER `display_name`,
                    MODIFY COLUMN `two_factor_secret` text DEFAULT NULL AFTER `password`,
                    MODIFY COLUMN `two_factor_recovery_codes` text DEFAULT NULL AFTER `two_factor_secret`,
                    MODIFY COLUMN `two_factor_confirmed_at` timestamp NULL DEFAULT NULL AFTER `two_factor_recovery_codes`,
                    MODIFY COLUMN `legacy_salted_password` varchar(32) NOT NULL AFTER `two_factor_confirmed_at`,
                    MODIFY COLUMN `remember_token` varchar(100) DEFAULT NULL AFTER `legacy_salted_password`,

                    -- Email
                    MODIFY COLUMN `email` varchar(64) NOT NULL AFTER `remember_token`,
                    MODIFY COLUMN `email_verified_at` timestamp NULL DEFAULT NULL AFTER `email`,
                    MODIFY COLUMN `email_original` varchar(255) DEFAULT NULL AFTER `email_verified_at`,

                    -- Authorization
                    MODIFY COLUMN `visible_role_id` bigint(20) unsigned DEFAULT NULL AFTER `email_original`,
                    MODIFY COLUMN `Permissions` tinyint(4) NOT NULL COMMENT '-2=spam, -1=banned, 0=unconfirmed, 1=confirmed, 2=jr-developer, 3=developer, 4=moderator' AFTER `visible_role_id`,

                    -- Player stats
                    MODIFY COLUMN `achievements_unlocked` int(10) unsigned DEFAULT NULL AFTER `Permissions`,
                    MODIFY COLUMN `achievements_unlocked_hardcore` int(10) unsigned DEFAULT NULL AFTER `achievements_unlocked`,
                    MODIFY COLUMN `completion_percentage_average` decimal(10,9) DEFAULT NULL AFTER `achievements_unlocked_hardcore`,
                    MODIFY COLUMN `completion_percentage_average_hardcore` decimal(10,9) DEFAULT NULL AFTER `completion_percentage_average`,
                    MODIFY COLUMN `points` int(11) DEFAULT 0 AFTER `completion_percentage_average_hardcore`,
                    MODIFY COLUMN `points_hardcore` int(11) NOT NULL AFTER `points`,
                    MODIFY COLUMN `points_weighted` int(10) unsigned DEFAULT NULL AFTER `points_hardcore`,

                    -- Developer stats
                    MODIFY COLUMN `yield_unlocks` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'The Number of awarded achievements that this user was the author of' AFTER `points_weighted`,
                    MODIFY COLUMN `yield_points` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'The total points allocated for achievements that this user has been the author of' AFTER `yield_unlocks`,

                    -- APIs
                    MODIFY COLUMN `connect_token` varchar(60) DEFAULT NULL AFTER `yield_points`,
                    MODIFY COLUMN `connect_token_expires_at` datetime DEFAULT NULL AFTER `connect_token`,
                    MODIFY COLUMN `web_api_key` varchar(60) DEFAULT NULL AFTER `connect_token_expires_at`,
                    MODIFY COLUMN `web_api_calls` int(10) unsigned NOT NULL DEFAULT 0 AFTER `web_api_key`,

                    -- Preferences/locale
                    MODIFY COLUMN `preferences_bitfield` int(10) unsigned DEFAULT 0 AFTER `web_api_calls`,
                    MODIFY COLUMN `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)) AFTER `preferences_bitfield`,
                    MODIFY COLUMN `country` varchar(255) DEFAULT NULL AFTER `preferences`,
                    MODIFY COLUMN `timezone` varchar(255) DEFAULT NULL AFTER `country`,
                    MODIFY COLUMN `locale` varchar(255) DEFAULT NULL AFTER `timezone`,
                    MODIFY COLUMN `locale_date` varchar(255) DEFAULT NULL AFTER `locale`,
                    MODIFY COLUMN `locale_number` varchar(255) DEFAULT NULL AFTER `locale_date`,

                    -- Activity/session
                    MODIFY COLUMN `last_activity_at` timestamp NULL DEFAULT NULL AFTER `locale_number`,
                    MODIFY COLUMN `rich_presence` varchar(255) DEFAULT NULL AFTER `last_activity_at`,
                    MODIFY COLUMN `rich_presence_game_id` int(10) unsigned NOT NULL DEFAULT 0 AFTER `rich_presence`,
                    MODIFY COLUMN `rich_presence_updated_at` datetime DEFAULT NULL AFTER `rich_presence_game_id`,

                    -- Profile
                    MODIFY COLUMN `motto` varchar(50) NOT NULL DEFAULT '' AFTER `rich_presence_updated_at`,
                    MODIFY COLUMN `is_user_wall_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Allow Posting to user wall' AFTER `motto`,
                    MODIFY COLUMN `unread_messages` int(10) unsigned DEFAULT NULL AFTER `is_user_wall_active`,

                    -- Moderation
                    MODIFY COLUMN `ManuallyVerified` tinyint(3) unsigned DEFAULT 0 COMMENT 'If 0, cannot post directly to forums without manual permission' AFTER `unread_messages`,
                    MODIFY COLUMN `forum_verified_at` timestamp NULL DEFAULT NULL AFTER `ManuallyVerified`,
                    MODIFY COLUMN `unranked_at` timestamp NULL DEFAULT NULL AFTER `forum_verified_at`,
                    MODIFY COLUMN `Untracked` tinyint(1) NOT NULL DEFAULT 0 AFTER `unranked_at`,
                    MODIFY COLUMN `banned_at` timestamp NULL DEFAULT NULL AFTER `Untracked`,
                    MODIFY COLUMN `muted_until` timestamp NULL DEFAULT NULL AFTER `banned_at`,

                    -- Timestamps
                    MODIFY COLUMN `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP AFTER `muted_until`,
                    MODIFY COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_at`,
                    MODIFY COLUMN `delete_requested_at` timestamp NULL DEFAULT NULL AFTER `updated_at`,
                    MODIFY COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `delete_requested_at`
            SQL);
        }
    }

    public function down(): void
    {
        // Drop new foreign key constraint.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_visible_role_id_foreign');
            });
        }

        // Restore original column order (before renaming columns back).
        DB::statement(<<<SQL
            ALTER TABLE users
                MODIFY COLUMN `ulid` char(26) DEFAULT NULL AFTER `id`,
                MODIFY COLUMN `username` varchar(32) NOT NULL AFTER `ulid`,
                MODIFY COLUMN `display_name` varchar(255) DEFAULT NULL AFTER `username`,
                MODIFY COLUMN `visible_role_id` bigint(20) unsigned DEFAULT NULL AFTER `display_name`,
                MODIFY COLUMN `password` varchar(255) DEFAULT NULL AFTER `visible_role_id`,
                MODIFY COLUMN `two_factor_secret` text DEFAULT NULL AFTER `password`,
                MODIFY COLUMN `two_factor_recovery_codes` text DEFAULT NULL AFTER `two_factor_secret`,
                MODIFY COLUMN `two_factor_confirmed_at` timestamp NULL DEFAULT NULL AFTER `two_factor_recovery_codes`,
                MODIFY COLUMN `legacy_salted_password` varchar(32) NOT NULL AFTER `two_factor_confirmed_at`,
                MODIFY COLUMN `email` varchar(64) NOT NULL AFTER `legacy_salted_password`,
                MODIFY COLUMN `email_verified_at` timestamp NULL DEFAULT NULL AFTER `email`,
                MODIFY COLUMN `remember_token` varchar(100) DEFAULT NULL AFTER `email_verified_at`,
                MODIFY COLUMN `Permissions` tinyint(4) NOT NULL COMMENT '-2=spam, -1=banned, 0=unconfirmed, 1=confirmed, 2=jr-developer, 3=developer, 4=moderator' AFTER `remember_token`,
                MODIFY COLUMN `achievements_unlocked` int(10) unsigned DEFAULT NULL AFTER `Permissions`,
                MODIFY COLUMN `achievements_unlocked_hardcore` int(10) unsigned DEFAULT NULL AFTER `achievements_unlocked`,
                MODIFY COLUMN `completion_percentage_average` decimal(10,9) DEFAULT NULL AFTER `achievements_unlocked_hardcore`,
                MODIFY COLUMN `completion_percentage_average_hardcore` decimal(10,9) DEFAULT NULL AFTER `completion_percentage_average`,
                MODIFY COLUMN `points` int(11) DEFAULT 0 AFTER `completion_percentage_average_hardcore`,
                MODIFY COLUMN `points_hardcore` int(11) NOT NULL AFTER `points`,
                MODIFY COLUMN `connect_token` varchar(60) DEFAULT NULL AFTER `points_hardcore`,
                MODIFY COLUMN `connect_token_expires_at` datetime DEFAULT NULL AFTER `connect_token`,
                MODIFY COLUMN `preferences_bitfield` int(10) unsigned DEFAULT 0 AFTER `connect_token_expires_at`,
                MODIFY COLUMN `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferences`)) AFTER `preferences_bitfield`,
                MODIFY COLUMN `country` varchar(255) DEFAULT NULL AFTER `preferences`,
                MODIFY COLUMN `timezone` varchar(255) DEFAULT NULL AFTER `country`,
                MODIFY COLUMN `locale` varchar(255) DEFAULT NULL AFTER `timezone`,
                MODIFY COLUMN `locale_date` varchar(255) DEFAULT NULL AFTER `locale`,
                MODIFY COLUMN `locale_number` varchar(255) DEFAULT NULL AFTER `locale_date`,
                MODIFY COLUMN `last_activity_at` timestamp NULL DEFAULT NULL AFTER `locale_number`,
                MODIFY COLUMN `motto` varchar(50) NOT NULL DEFAULT '' AFTER `last_activity_at`,
                MODIFY COLUMN `yield_unlocks` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'The Number of awarded achievements that this user was the author of' AFTER `motto`,
                MODIFY COLUMN `yield_points` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'The total points allocated for achievements that this user has been the author of' AFTER `yield_unlocks`,
                MODIFY COLUMN `web_api_key` varchar(60) DEFAULT NULL AFTER `yield_points`,
                MODIFY COLUMN `web_api_calls` int(10) unsigned NOT NULL DEFAULT 0 AFTER `web_api_key`,
                MODIFY COLUMN `rich_presence_game_id` int(10) unsigned NOT NULL DEFAULT 0 AFTER `web_api_calls`,
                MODIFY COLUMN `rich_presence` varchar(255) DEFAULT NULL AFTER `rich_presence_game_id`,
                MODIFY COLUMN `rich_presence_updated_at` datetime DEFAULT NULL AFTER `rich_presence`,
                MODIFY COLUMN `ManuallyVerified` tinyint(3) unsigned DEFAULT 0 COMMENT 'If 0, cannot post directly to forums without manual permission' AFTER `rich_presence_updated_at`,
                MODIFY COLUMN `forum_verified_at` timestamp NULL DEFAULT NULL AFTER `ManuallyVerified`,
                MODIFY COLUMN `unranked_at` timestamp NULL DEFAULT NULL AFTER `forum_verified_at`,
                MODIFY COLUMN `banned_at` timestamp NULL DEFAULT NULL AFTER `unranked_at`,
                MODIFY COLUMN `muted_until` timestamp NULL DEFAULT NULL AFTER `banned_at`,
                MODIFY COLUMN `unread_messages` int(10) unsigned DEFAULT NULL AFTER `muted_until`,
                MODIFY COLUMN `points_weighted` int(10) unsigned DEFAULT NULL AFTER `unread_messages`,
                MODIFY COLUMN `is_user_wall_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Allow Posting to user wall' AFTER `points_weighted`,
                MODIFY COLUMN `Untracked` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_user_wall_active`,
                MODIFY COLUMN `email_original` varchar(255) DEFAULT NULL AFTER `Untracked`,
                MODIFY COLUMN `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP AFTER `email_original`,
                MODIFY COLUMN `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_at`,
                MODIFY COLUMN `delete_requested_at` timestamp NULL DEFAULT NULL AFTER `updated_at`,
                MODIFY COLUMN `deleted_at` timestamp NULL DEFAULT NULL AFTER `delete_requested_at`
        SQL);

        // Rename indexes back.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->renameIndex('users_display_name_unique', 'useraccounts_display_name_unique');
                $table->renameIndex('users_ulid_unique', 'useraccounts_ulid_unique');
                $table->renameIndex('users_web_api_key_index', 'users_apikey_index');
                $table->renameIndex('users_connect_token_index', 'users_apptoken_index');
                $table->renameIndex('users_last_activity_at_deleted_at_index', 'useraccounts_lastlogin_deleted_index');
                $table->renameIndex('users_visible_role_id_index', 'useraccounts_visible_role_id_index');
            });
        }

        // Rename table back.
        Schema::rename('users', 'UserAccounts');

        // Rename columns back.
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->renameColumn('id', 'ID');
            $table->renameColumn('username', 'User');
            $table->renameColumn('password', 'Password');
            $table->renameColumn('legacy_salted_password', 'SaltedPass');
            $table->renameColumn('email', 'EmailAddress');
            $table->renameColumn('points_hardcore', 'RAPoints');
            $table->renameColumn('points', 'RASoftcorePoints');
            $table->renameColumn('connect_token', 'appToken');
            $table->renameColumn('connect_token_expires_at', 'appTokenExpiry');
            $table->renameColumn('preferences_bitfield', 'websitePrefs');
            $table->renameColumn('last_activity_at', 'LastLogin');
            $table->renameColumn('motto', 'Motto');
            $table->renameColumn('yield_unlocks', 'ContribCount');
            $table->renameColumn('yield_points', 'ContribYield');
            $table->renameColumn('web_api_key', 'APIKey');
            $table->renameColumn('web_api_calls', 'APIUses');
            $table->renameColumn('rich_presence_game_id', 'LastGameID');
            $table->renameColumn('rich_presence', 'RichPresenceMsg');
            $table->renameColumn('rich_presence_updated_at', 'RichPresenceMsgDate');
            $table->renameColumn('unread_messages', 'UnreadMessageCount');
            $table->renameColumn('points_weighted', 'TrueRAPoints');
            $table->renameColumn('is_user_wall_active', 'UserWallActive');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');
            $table->renameColumn('delete_requested_at', 'DeleteRequested');
            $table->renameColumn('deleted_at', 'Deleted');
            $table->renameColumn('email_original', 'email_backup');
        });

        // Recreate original foreign key constraint.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('UserAccounts', function (Blueprint $table) {
                $table->foreign('visible_role_id', 'useraccounts_visible_role_id_foreign')
                    ->references('id')
                    ->on('auth_roles')
                    ->onDelete('set null');
            });
        }
    }
};
