<?php

declare(strict_types=1);

use App\Community\Enums\TicketState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $quarantineEmulatorIds = DB::table('emulators')
            ->where('can_debug_triggers', false)
            ->pluck('id');

        if ($quarantineEmulatorIds->isEmpty()) {
            return;
        }

        DB::table('tickets')
            ->whereIn('emulator_id', $quarantineEmulatorIds)
            ->whereIn('state', [TicketState::Open->value, TicketState::Request->value])
            ->update(['state' => TicketState::Quarantined->value]);
    }

    public function down(): void
    {
        // irreversible
    }
};
