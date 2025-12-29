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
            $table->renameColumn('RAPoints', 'points');
            $table->renameColumn('RASoftcorePoints', 'points_softcore');
            $table->renameColumn('appToken', 'connect_token');
            $table->renameColumn('appTokenExpiry', 'connect_token_expires_at');
            $table->renameColumn('websitePrefs', 'preferences_bitfield');
            $table->renameColumn('LastLogin', 'last_activity_at');
            $table->renameColumn('Motto', 'motto');
            $table->renameColumn('ContribCount', 'yield_unlocks');
            $table->renameColumn('ContribYield', 'yield_points');
            $table->renameColumn('APIKey', 'web_api_key');
            $table->renameColumn('APIUses', 'web_api_calls');
            $table->renameColumn('LastGameID', 'last_game_id');
            $table->renameColumn('RichPresenceMsg', 'rich_presence');
            $table->renameColumn('RichPresenceMsgDate', 'rich_presence_updated_at');
            $table->renameColumn('UnreadMessageCount', 'unread_messages');
            $table->renameColumn('TrueRAPoints', 'points_weighted');
            $table->renameColumn('UserWallActive', 'is_user_wall_active');
            $table->renameColumn('Created', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
            $table->renameColumn('DeleteRequested', 'delete_requested_at');
            $table->renameColumn('Deleted', 'deleted_at');
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
    }

    public function down(): void
    {
        // Drop new foreign key constraint.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign('users_visible_role_id_foreign');
            });
        }

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
            $table->renameColumn('points', 'RAPoints');
            $table->renameColumn('points_softcore', 'RASoftcorePoints');
            $table->renameColumn('connect_token', 'appToken');
            $table->renameColumn('connect_token_expires_at', 'appTokenExpiry');
            $table->renameColumn('preferences_bitfield', 'websitePrefs');
            $table->renameColumn('last_activity_at', 'LastLogin');
            $table->renameColumn('motto', 'Motto');
            $table->renameColumn('yield_unlocks', 'ContribCount');
            $table->renameColumn('yield_points', 'ContribYield');
            $table->renameColumn('web_api_key', 'APIKey');
            $table->renameColumn('web_api_calls', 'APIUses');
            $table->renameColumn('last_game_id', 'LastGameID');
            $table->renameColumn('rich_presence', 'RichPresenceMsg');
            $table->renameColumn('rich_presence_updated_at', 'RichPresenceMsgDate');
            $table->renameColumn('unread_messages', 'UnreadMessageCount');
            $table->renameColumn('points_weighted', 'TrueRAPoints');
            $table->renameColumn('is_user_wall_active', 'UserWallActive');
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');
            $table->renameColumn('delete_requested_at', 'DeleteRequested');
            $table->renameColumn('deleted_at', 'Deleted');
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
