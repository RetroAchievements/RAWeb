<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('achievement_maintainers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('achievement_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamp('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('achievement_maintainers', function (Blueprint $table) {
            $table->foreign('achievement_id')
                ->references('ID')
                ->on('Achievements')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('ID')
                ->on('UserAccounts')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievement_maintainers');
    }
};
