<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        // sync target for Activity (three months)
        Schema::create('user_activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('type');

            $table->nullableMorphs('subject');
            $table->unsignedBigInteger('subject_context')->nullable();

            $table->timestampsTz();

            $table->foreign('user_id', 'user_activities_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });

        /*
         * https://github.com/maynagashev/laravel-social-connections/blob/master/src/migrations/2017_02_27_152820_create_social_logins_table.php
         */
        Schema::create('user_connections', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('provider', 60);
            $table->string('provider_user_id')->nullable();
            $table->string('token')->nullable();
            $table->string('token_secret')->nullable();
            $table->string('refresh_token')->nullable();
            $table->string('expires')->nullable();
            $table->string('nickname')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('avatar')->nullable();
            $table->string('url')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->unique(['user_id', 'provider', 'provider_user_id']);
            $table->index(['provider', 'provider_user_id']);
        });

        Schema::table('Friends', function (Blueprint $table) {
            if (DB::connection()->getDriverName() !== 'sqlite') {
                // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
                $table->bigIncrements('id')->first();
            }
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->unsignedBigInteger('related_user_id')->nullable()->after('user_id');
            $table->unsignedSmallInteger('status')->nullable()->after('related_user_id');

            $table->foreign('related_user_id', 'user_relations_related_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('user_id', 'user_relations_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_connections');
        Schema::dropIfExists('user_activities');

        // Schema::table('Activity', function (Blueprint $table) {
        //     $table->dropForeign('user_activities_user_id_foreign');
        //     $table->dropColumn('user_id');
        //     $table->dropColumn('subject_type');
        //     $table->dropColumn('subject_id');
        //     $table->dropColumn('subject_context');
        // });

        Schema::table('Friends', function (Blueprint $table) {
            $table->dropForeign('user_relations_related_user_id_foreign');
            $table->dropForeign('user_relations_user_id_foreign');
            $table->dropColumn('id');
            $table->dropColumn('user_id');
            $table->dropColumn('related_user_id');
            $table->dropColumn('status');
        });
    }
};
