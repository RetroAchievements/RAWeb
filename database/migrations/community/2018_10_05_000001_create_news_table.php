<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('News', function (Blueprint $table) {
            $table->bigIncrements('ID')->change();

            $table->text('lead')->nullable()->after('Title');

            // nullable as some authors might disappear
            $table->unsignedBigInteger('user_id')->nullable()->after('Author');

            // allow to schedule publishing/unpublishing of posts
            $table->timestampTz('publish_at')->nullable();
            $table->timestampTz('unpublish_at')->nullable();

            $table->softDeletesTz();

            $table->foreign('user_id', 'news_user_id_foreign')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('News', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('lead');
            $table->dropColumn('publish_at');
            $table->dropColumn('unpublish_at');
            $table->dropSoftDeletesTz();
        });
    }
};
