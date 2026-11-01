<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const QUARANTINED_GAME_IDS = [
        28275, // Final Fantasy XI
        28317, // Final Fantasy XI: Rise of the Zilart
        28359, // Final Fantasy XI: Chains of Promathia
        41186, // Final Fantasy XI: Treasures of Aht Urhgan
        44031, // Ragnarok Online
        45053, // Poring Clicker
    ];

    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->boolean('is_ticket_quarantine_enabled')->default(false)->after('is_media_restricted');
        });

        DB::table('games')->whereIn('id', self::QUARANTINED_GAME_IDS)->update(['is_ticket_quarantine_enabled' => true]);
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('is_ticket_quarantine_enabled');
        });
    }
};
