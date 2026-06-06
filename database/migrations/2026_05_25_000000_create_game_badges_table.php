<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('game_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();

            // "/Images/NNNNNN.png"
            $table->string('image_asset_path');

            // if the game goes Badge X -> Badge Y -> Badge X, we can use the stored SHA1
            // values to be smart enough to only show Badge X and Badge Y as selectable (2 images),
            // rather than two copies of Badge X and Badge Y (3 images).
            $table->string('sha1', 40);

            // which signal produced this row (live upload, audit-log backfill, comment heuristic, etc).
            // lets us re-run a single backfill layer in prod without having to nuke the entire table.
            $table->string('attribution_source');

            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // the most recent time this badge became canonical for a game id.
            // when there's thrashing between two badges (X -> Y -> X), this is the timestamp
            // of the second activation, not the first.
            $table->timestamp('became_current_at');

            // if this field is null, then this is the canonical badge for a game id.
            $table->timestamp('replaced_at')->nullable();

            $table->timestamps();

            // to better support a cleanup tool in Filament for removing things
            // that were added in the past that have been rightfully replaced.
            // soft deletes makes it easy to revert a delete if something goes wrong.
            $table->softDeletes();

            // one row per unique badge per game
            $table->unique(['game_id', 'sha1']);

            // "find the current canonical badge for this game"
            $table->index(['game_id', 'replaced_at']);

            // "list this game's badges in chronological order"
            $table->index(['game_id', 'became_current_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_badges');
    }
};
