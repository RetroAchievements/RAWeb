<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->boolean('supports_upscaled_screenshots')->default(true)->after('has_analog_tv_output');
        });

        // Systems with 3D-capable hardware where emulators commonly
        // upscale beyond native resolution. These keep the default of true.
        $supportsUpscaledScreenshots = [
            2,   // Nintendo 64
            12,  // PlayStation
            16,  // GameCube
            18,  // Nintendo DS
            19,  // Wii
            20,  // Wii U
            21,  // PlayStation 2
            22,  // Xbox
            39,  // Saturn
            40,  // Dreamcast
            41,  // PlayStation Portable
            42,  // Philips CD-i
            43,  // 3DO Interactive Multiplayer
            61,  // Nokia N-Gage
            62,  // Nintendo 3DS
            70,  // Zeebo
            78,  // Nintendo DSi
            101, // Events
            102, // Standalone
        ];

        DB::table('systems')
            ->whereNotIn('id', $supportsUpscaledScreenshots)
            ->update(['supports_upscaled_screenshots' => false]);
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn('supports_upscaled_screenshots');
        });
    }
};
