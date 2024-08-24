<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('ForumTopic', function (Blueprint $table) {
            $table->index(
                ['RequiredPermissions', 'deleted_at', 'LatestCommentID'],
                'idx_permissions_deleted_latest'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ForumTopic', function (Blueprint $table) {
            $table->dropIndex('idx_permissions_deleted_latest');
        });
    }
};
