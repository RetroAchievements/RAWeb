<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('platforms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('execution_environment')->nullable();
            $table->timestamps();
        });

        Schema::create('emulator_platforms', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('emulator_id');
            $table->unsignedBigInteger('platform_id');
            $table->timestamps();
        });

        Schema::table('emulator_platforms', function (Blueprint $table) {
            $table->unique(['emulator_id', 'platform_id']);

            $table->foreign('emulator_id')
                ->references('id')
                ->on('emulators')
                ->onDelete('cascade');

            $table->foreign('platform_id')
                ->references('id')
                ->on('platforms')
                ->onDelete('cascade');
        });

        Schema::create('emulator_downloads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('emulator_id');
            $table->unsignedBigInteger('platform_id');
            $table->string('label')->nullable();
            $table->string('url');
            $table->timestamps();
        });

        Schema::table('emulator_downloads', function (Blueprint $table) {
            $table->foreign('emulator_id')
                ->references('id')
                ->on('emulators')
                ->onDelete('cascade');

            $table->foreign('platform_id')
                ->references('id')
                ->on('platforms')
                ->onDelete('cascade');
        });

        Schema::table('emulators', function (Blueprint $table) {
            $table->string('website_url')->after('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('emulators', function (Blueprint $table) {
            $table->dropColumn('website_url');
        });

        Schema::table('emulator_downloads', function (Blueprint $table) {
            $table->dropForeign(['emulator_id']);
            $table->dropForeign(['platform_id']);
        });

        Schema::table('emulator_platforms', function (Blueprint $table) {
            $table->dropForeign(['emulator_id']);
            $table->dropForeign(['platform_id']);
        });

        Schema::dropIfExists('emulator_downloads');
        Schema::dropIfExists('emulator_platforms');
        Schema::dropIfExists('platforms');
    }
};
