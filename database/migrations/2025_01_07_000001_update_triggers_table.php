<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('triggers', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('triggers')
                ->nullOnDelete();

            $table->index('parent_id');
        });

        Schema::table('triggers', function (Blueprint $table) {
            $table->dropColumn(['type', 'stat', 'stat_goal', 'stat_format']);
        });
    }

    public function down(): void
    {
        Schema::table('triggers', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);

            $table->text('type')->nullable();
            $table->string('stat')->nullable();
            $table->string('stat_goal')->nullable();
            $table->string('stat_format', 50)->nullable();
        });
    }
};
