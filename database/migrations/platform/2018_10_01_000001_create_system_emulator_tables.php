<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        Schema::create('systems', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_full')->nullable();
            $table->string('name_short')->nullable();
            $table->string('manufacturer')->nullable();
            $table->unsignedInteger('order_column')->nullable();

            $table->boolean('active')->nullable();

            /*
             * let's keep cached values out of this for now
             * TODO: check back in a while
             */
            // $table->unsignedInteger('points_total')->nullable();
            // $table->unsignedInteger('points_weighted')->nullable();
            // $table->unsignedInteger('achievements_total')->nullable();
            // $table->unsignedInteger('achievements_published')->nullable();
            // $table->unsignedInteger('achievements_unpublished')->nullable();

            /*
             * earlier creation dates are not known
             */
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('emulators', function (Blueprint $table) {
            $table->increments('id');
            $table->string('integration_id')->nullable();
            $table->string('name')->nullable();
            $table->string('handle')->nullable();
            $table->text('description')->nullable();
            $table->text('link')->nullable();

            /*
             * which
             */
            $table->text('game_hash_column')->nullable();

            $table->unsignedInteger('order_column')->nullable();

            $table->boolean('active')->default(false);
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique('integration_id');
        });

        Schema::create('system_emulators', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('system_id')->unsigned();
            $table->unsignedInteger('emulator_id')->unsigned();
            $table->timestampsTz();

            $table->foreign('system_id')->references('id')->on('systems')->onDelete('cascade');
            $table->foreign('emulator_id')->references('id')->on('emulators')->onDelete('cascade');
        });

        Schema::create('emulator_releases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('emulator_id')->unsigned();
            $table->string('version')->nullable();
            $table->boolean('stable')->default(false);
            $table->boolean('minimum')->default(false);
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique(['emulator_id', 'version']);
            $table->foreign('emulator_id')->references('id')->on('emulators')->onDelete('cascade');
        });

        Schema::create('integration_releases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('version');
            $table->boolean('stable')->default(false);
            $table->boolean('minimum')->default(false);
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->unique('version');
        });
    }

    public function down()
    {
        Schema::dropIfExists('emulator_releases');
        Schema::dropIfExists('integration_releases');

        Schema::dropIfExists('system_emulators');
        Schema::dropIfExists('emulators');
        Schema::dropIfExists('systems');
    }
};
