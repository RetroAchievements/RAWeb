<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('event_winner_discord_role_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('discord_role_id', 20); // 18-19 chars long

            // nullable for the "they unlocked it, but weren't found in Discord" case.
            // we'll stop rechecking for them. people joining the server mid-week and not
            // getting caught is a known trade-off.
            $table->string('discord_user_id', 20)->nullable(); // 17-19 chars long

            // stores when the role grant ends.
            // different events may have different expiry dates.
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->unique(['discord_role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_winner_discord_role_grants');
    }
};
