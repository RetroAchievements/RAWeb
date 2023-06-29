<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Rating', function (Blueprint $table) {
            $table->dropPrimary(['User', 'RatingObjectType', 'RatingID']);
        });

        Schema::table('Rating', function (Blueprint $table) {
            // TODO remove as soon as SQLite was upgraded to 3.37+ via Ubuntu upgrade from 20.04 -> 22.04
            if (DB::connection()->getDriverName() !== 'sqlite') {
                $table->bigIncrements('id')->first();
            }

            $table->unsignedBigInteger('user_id')->nullable()->after('id');

            // nullable morphs
            $table->string('ratable_model')->nullable()->after('ID');
            $table->unsignedBigInteger('ratable_id')->nullable()->after('ratable_model');
            $table->index(['ratable_model', 'ratable_id'], 'ratings_ratable_index');

            // drop this in favor of ratable morph
            // kept to make sure only unique ratings exist
            $table->unique(['User', 'RatingObjectType', 'RatingID'], 'ratings_user_rating_unique');

            $table->foreign('user_id', 'ratings_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('Rating', function (Blueprint $table) {
            $table->dropIndex('ratings_ratable_index');
            $table->dropUnique('ratings_user_rating_unique');
            $table->dropForeign('ratings_user_id_foreign');
            $table->dropColumn('id');
            $table->dropColumn('ratable_model');
            $table->dropColumn('ratable_id');
            $table->dropColumn('user_id');
        });

        Schema::table('Rating', function (Blueprint $table) {
            $table->primary(['User', 'RatingObjectType', 'RatingID']);
        });
    }
};
