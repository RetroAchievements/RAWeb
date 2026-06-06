<?php

declare(strict_types=1);

use App\Models\System;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        /**
         * @see https://github.com/RetroAchievements/docs/pull/336/changes#diff-26905e1c2799c9e151ede26bda397ec2040ce9f0b405a10b11ebfeb1530554c3R123-R124
         */
        $resolutions = json_encode([
            ['width' => 160, 'height' => 228],  // NTSC
            ['width' => 160, 'height' => 274],  // PAL
        ]);

        DB::table('systems')->where('id', System::Atari2600)->update([
            'screenshot_resolutions' => $resolutions,
        ]);
    }

    public function down(): void
    {
        DB::table('systems')->where('id', System::Atari2600)->update([
            'screenshot_resolutions' => null,
        ]);
    }
};
