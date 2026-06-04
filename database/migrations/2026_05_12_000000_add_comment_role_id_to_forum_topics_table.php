<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('forum_topics', function (Blueprint $table) {
            $table->unsignedBigInteger('comment_role_id')->nullable()->after('required_permissions');

            $table->foreign('comment_role_id')
                ->references('id')
                ->on('auth_roles')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('forum_topics', function (Blueprint $table) {
            $table->dropForeign(['comment_role_id']);
            $table->dropColumn('comment_role_id');
        });
    }
};
