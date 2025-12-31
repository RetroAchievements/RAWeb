<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('Friends', function (Blueprint $table) {
            $table->string('status', 20)->nullable()->change();
        });

        DB::statement("UPDATE Friends SET status = CASE
            WHEN Friendship = -1 THEN 'blocked'
            WHEN Friendship = 0 THEN 'not_following'
            WHEN Friendship = 1 THEN 'following'
        END");

        Schema::table('Friends', function (Blueprint $table) {
            $table->string('status', 20)->nullable(false)->change();

            $table->dropColumn('Friendship');

            $table->renameColumn('Created', 'created_at');
            $table->renameColumn('Updated', 'updated_at');
        });

        Schema::rename('Friends', 'user_relations');
    }

    public function down(): void
    {
        Schema::rename('user_relations', 'Friends');

        Schema::table('Friends', function (Blueprint $table) {
            $table->renameColumn('created_at', 'Created');
            $table->renameColumn('updated_at', 'Updated');

            $table->tinyInteger('Friendship')->after('related_user_id')->default(0);
        });

        DB::statement("UPDATE Friends SET Friendship = CASE
            WHEN status = 'blocked' THEN -1
            WHEN status = 'not_following' THEN 0
            WHEN status = 'following' THEN 1
            ELSE 0
        END");

        Schema::table('Friends', function (Blueprint $table) {
            $table->smallInteger('status')->unsigned()->nullable()->change();
        });
    }
};
