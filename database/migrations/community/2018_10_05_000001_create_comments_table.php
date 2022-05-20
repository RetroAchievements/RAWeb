<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('Comment', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();

            $table->unsignedBigInteger('UserID')->nullable()->change();

            // nullable morphs
            $table->string('commentable_type')->nullable()->after('UserID');
            $table->unsignedBigInteger('commentable_id')->nullable()->after('commentable_type');

            $table->softDeletesTz();

            // nullable morphs
            $table->index(['commentable_type', 'commentable_id'], 'comments_commentable_index');
        });
    }

    public function down(): void
    {
        Schema::table('Comment', function (Blueprint $table) {
            $table->dropIndex('comments_commentable_index');
            $table->dropColumn('commentable_type');
            $table->dropColumn('commentable_id');
            $table->dropSoftDeletesTz();
        });
    }
};
