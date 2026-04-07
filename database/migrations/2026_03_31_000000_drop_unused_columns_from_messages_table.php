<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'Unread')) {
                $table->dropColumn('Unread');
            }
            if (Schema::hasColumn('messages', 'Title')) {
                $table->dropColumn('Title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->boolean('Unread')->default(true)->after('created_at');
            $table->string('Title')->default('')->after('Unread');
        });
    }
};
