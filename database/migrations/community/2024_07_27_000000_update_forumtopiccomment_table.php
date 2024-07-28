<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->index(['ForumTopicID', 'Authorised', 'DateCreated']);
        });
    }

    public function down(): void
    {
        Schema::table('ForumTopicComment', function (Blueprint $table) {
            $table->dropIndex(['ForumTopicID', 'Authorised', 'DateCreated']);
        });
    }
};
