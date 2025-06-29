<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('player_progress_resets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('initiated_by_user_id')->nullable(); // if it's null, assume the player reset their own stuff
            $table->string('type', 20); // see PlayerProgressResetType
            $table->unsignedBigInteger('type_id')->nullable(); // this is a polymorphic reference
            $table->timestamps();

            // add some covering indexes for common queries
            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['user_id', 'type', 'type_id', 'created_at']);

            $table->foreign('user_id')->references('ID')->on('UserAccounts')->onDelete('cascade');
            $table->foreign('initiated_by_user_id')->references('ID')->on('UserAccounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_progress_resets');
    }
};
