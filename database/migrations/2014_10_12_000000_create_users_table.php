<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');

            /*
             * used for login currently but should be vanity, really
             */
            $table->string('username');

            /*
             * let users have another name on their profile
             */
            $table->string('display_name')->nullable();

            /*
             * strongly hashed password
             */
            $table->string('password')->nullable();

            $table->timestampTz('last_login_at')->nullable();
            $table->timestampTz('last_activity_at')->nullable();

            /*
             * nullable email -> there is some legacy here...
             */
            $table->string('email')->nullable();
            $table->timestampTz('email_verified_at')->nullable();

            $table->json('preferences')->nullable();
            /*
             * TODO: move this to preferences, allow comments to be visible to/writable for public, friends, private etc
             */
            // $table->boolean('wall_active')->nullable()->default(true);

            $table->string('motto')->nullable();

            /*
             * dynamic relationship to this user's activity
             */
            // $table->unsignedInteger('last_activity_id')->nullable();

            /*
             * token for web auth middleware
             */
            $table->rememberToken();

            $table->string('country')->nullable();
            $table->string('timezone')->nullable();
            $table->string('locale')->nullable();
            $table->string('locale_date')->nullable();
            $table->string('locale_number')->nullable();

            /*
             * token for RPC API auth middleware
             * TODO: move to passport?
             */
            $table->string('connect_token', 60)->nullable();
            $table->timestampTz('connect_token_expires_at')->nullable();

            /*
             * token for web api auth middleware
             * we love tokens...
             * TODO: move to passport?
             */
            $table->string('api_token')->nullable();
            $table->unsignedInteger('api_calls')->nullable();

            /*
             * moved to dynamic relationship to game trips ... eh, sessions
             */
            // $table->unsignedInteger('last_game_id')->nullable();

            /*
             * moved to game sessions
             */
            // $table->string('rich_presence')->nullable();
            // $table->timestampTz('rich_presence_updated_at')->nullable();

            /*
             * some additional states
             */
            $table->timestampTz('forum_verified_at')->nullable();

            $table->timestampTz('unranked_at')->nullable();

            $table->timestampTz('banned_at')->nullable();

            $table->timestampTz('muted_until')->nullable();

            /*
             * some cached metrics
             * should be moved to dynamic relationships where it makes sense
             * TODO: move those to another place that really likes cached values? like, a cache
             */
            $table->unsignedInteger('points_total')->nullable();
            $table->unsignedInteger('points_weighted')->nullable();

            // $table->unsignedInteger('achievements_unlocked_yield')->nullable();
            // $table->unsignedInteger('achievements_points_yield')->nullable();
            // $table->unsignedInteger('achievements_unlocked')->nullable();
            // $table->unsignedInteger('achievements_unlocked_hardcore')->nullable();
            // $table->decimal('completion_percentage_average', 10, 9)->nullable();
            // $table->decimal('completion_percentage_average_hardcore', 10, 9)->nullable();

            /*
             * dynamic
             */
            // $table->unsignedInteger('unread_messages_count')->nullable();

            /*
             * earlier creation dates are not known
             */
            $table->timestampsTz();
            $table->timestampTz('delete_requested_at')->nullable();
            $table->softDeletesTz();

            /*
             * TODO check usernames are actually unique
             */
            $table->unique('username');

            /*
             * TODO hopefully, some day, email addresses are unique and can be used as login instead of the username
             */
            $table->index('email');

            $table->index('last_login_at');
            $table->index('points_total');
            $table->index('points_weighted');
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

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_usernames');
        Schema::dropIfExists('users');
    }
};
