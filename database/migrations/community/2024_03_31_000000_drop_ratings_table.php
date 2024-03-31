<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('Rating');
    }

    public function down(): void
    {
        if (!Schema::hasTable('Rating')) {
            Schema::create('Rating', function (Blueprint $table) {
                $table->string('User');
                $table->smallInteger('RatingObjectType');
                $table->smallInteger('RatingID');
                $table->smallInteger('RatingValue');

                $table->primary(['User', 'RatingObjectType', 'RatingID']);
            });
        }

        if (!Schema::hasColumns('Rating', ['Created', 'Updated'])) {
            Schema::table('Rating', function (Blueprint $table) {
                $table->timestampTz('Created')->nullable()->useCurrent();
                $table->timestampTz('Updated')->nullable()->useCurrent()->useCurrentOnUpdate();
            });
        }
    }
};
