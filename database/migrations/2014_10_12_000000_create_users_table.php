<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('UserAccounts', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();

            /*
             * let users have another name on their profile
             */
            $table->string('display_name')->after('User')->nullable();

            /*
             * nullable email -> there is some legacy here...
             */
            $table->timestampTz('email_verified_at')->nullable()->after('EmailAddress');

            /*
             * token for web auth middleware
             */
            $table->rememberToken()->after('email_verified_at');

            $table->json('preferences')->nullable()->after('websitePrefs');

            $table->string('country')->nullable()->after('preferences');
            $table->string('timezone')->nullable()->after('country');
            $table->string('locale')->nullable()->after('timezone');
            $table->string('locale_date')->nullable()->after('locale');
            $table->string('locale_number')->nullable()->after('locale_date');

            /*
             * status timestamps
             */
            $table->timestampTz('forum_verified_at')->nullable()->after('ManuallyVerified');
            $table->timestampTz('unranked_at')->nullable()->after('forum_verified_at');
            $table->timestampTz('banned_at')->nullable()->after('unranked_at');
            $table->timestampTz('muted_until')->nullable()->after('banned_at');

            /*
             * metrics
             */
            $table->unsignedInteger('achievements_unlocked')->nullable()->after('Permissions');
            $table->unsignedInteger('achievements_unlocked_hardcore')->nullable()->after('achievements_unlocked');
            $table->decimal('completion_percentage_average', 10, 9)->nullable()->after('achievements_unlocked_hardcore');
            $table->decimal('completion_percentage_average_hardcore', 10, 9)->nullable()->after('completion_percentage_average');

            $table->index('unranked_at', 'users_unranked_at_index');
            $table->index(['RAPoints', 'unranked_at'], 'users_points_unranked_at_index');
            $table->index(['RASoftcorePoints', 'unranked_at'], 'users_points_softcore_unranked_at_index');
            $table->index(['TrueRAPoints', 'unranked_at'], 'users_points_weighted_unranked_at_index');
        });

        /*
         * store which user has which usernames
         * may be used to do redirects after username changes
         */
        Schema::create('user_usernames', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('username');

            $table->timestampsTz();

            $table->unique(['user_id', 'username']);
            $table->index('username');

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_usernames');
        Schema::table('UserAccounts', function (Blueprint $table) {

            $table->dropIndex('users_points_unranked_at_index');
            $table->dropIndex('users_points_softcore_unranked_at_index');
            $table->dropIndex('users_points_weighted_unranked_at_index');

            $table->dropColumn('display_name');
            $table->dropColumn('email_verified_at');
            $table->dropRememberToken();
            $table->dropColumn('preferences');
            $table->dropColumn('country');
            $table->dropColumn('timezone');
            $table->dropColumn('locale');
            $table->dropColumn('locale_date');
            $table->dropColumn('locale_number');
            $table->dropColumn('forum_verified_at');
            $table->dropColumn('unranked_at');
            $table->dropColumn('banned_at');
            $table->dropColumn('muted_until');
            $table->dropColumn('achievements_unlocked');
            $table->dropColumn('achievements_unlocked_hardcore');
            $table->dropColumn('completion_percentage_average');
            $table->dropColumn('completion_percentage_average_hardcore');
        });
    }
};
