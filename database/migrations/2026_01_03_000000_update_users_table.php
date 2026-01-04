<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_username_untracked_index');
            $table->dropIndex('users_points_weighted_untracked_index');
            $table->dropIndex('users_untracked_points_index');
            $table->dropIndex('users_points_untracked_index');
            $table->dropIndex('users_points_softcore_untracked_index');

            $table->dropColumn('Untracked');

            $table->index(['username', 'unranked_at'], 'users_username_unranked_at_index');
            $table->index(['unranked_at', 'points_hardcore'], 'users_unranked_at_points_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_username_unranked_at_index');
            $table->dropIndex('users_unranked_at_points_index');

            $table->boolean('Untracked')->default(false)->after('unranked_at');

            $table->index(['username', 'Untracked'], 'users_username_untracked_index');
            $table->index(['points_weighted', 'Untracked'], 'users_points_weighted_untracked_index');
            $table->index(['Untracked', 'points_hardcore'], 'users_untracked_points_index');
            $table->index(['points_hardcore', 'Untracked'], 'users_points_untracked_index');
            $table->index(['points', 'Untracked'], 'users_points_softcore_untracked_index');
        });
    }
};
