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
            $table->json('screenshot_resolutions')->nullable()->after('name_short');
            $table->boolean('has_analog_tv_output')->default(false)->after('screenshot_resolutions');
        });

        /**
         * Seed known system screenshot resolutions.
         *
         * Each entry is a JSON array of {width, height} objects representing
         * the RA community's editorial standard dimensions for screenshots.
         *
         * Sources:
         * - https://docs.retroachievements.org/guidelines/content/game-info-and-hub-guidelines.html#screenshot-dimensions
         * - https://www.mobygames.com/info/screenshots/
         *
         * The first resolution in each array is the "most common", and it's
         * used as the default for layout shift prevention in the front-end.
         *
         * Integer multiples (2x, 3x) of these base resolutions are also
         * accepted at upload time for all systems. Systems with
         * has_analog_tv_output=true additionally accept SMPTE 601 analog
         * capture resolutions (704x480, 720x480, 720x486, 704x576, 720x576).
         *
         * Unlisted systems stay null, meaning the resolution varies per game
         * and no dimension validation is applied at upload time.
         */
        $resolutions = [

            // =================================================================
            // HANDHELDS - Fixed LCD resolutions, no analog TV output.
            // =================================================================

            // Pokemon Mini - Fixed 96x64 monochrome LCD.
            24 => [[96, 64]],

            // Arduboy - Fixed 128x64 1-bit OLED.
            71 => [[128, 64]],

            // Game Gear - Fixed 160x144 LCD viewport.
            15 => [[160, 144]],

            // Game Boy - Fixed 160x144 dot-matrix LCD.
            4 => [[160, 144]],

            // Game Boy Color - Same 160x144 LCD as Game Boy.
            6 => [[160, 144]],

            // Mega Duck - Fixed 160x144 LCD (Game Boy clone hardware).
            69 => [[160, 144]],

            // Atari Lynx - Fixed 160x102 backlit LCD.
            13 => [[160, 102]],

            // Neo Geo Pocket / Color - Fixed 160x152 TFT LCD.
            14 => [[160, 152]],

            // WASM-4 - Spec defines 160x160 at 4 colors.
            72 => [[160, 160]],

            // Watara Supervision - Fixed 160x160 monochrome LCD (4 shades).
            63 => [[160, 160]],

            // Nintendo DS - 256x192 per screen, 256x384 stacked.
            18 => [[256, 384], [256, 192]],

            // Nintendo DSi - Same dual-screen layout as DS.
            78 => [[256, 384], [256, 192]],

            // WonderSwan / Color - 224x144 LCD with physical rotation.
            // Games run horizontal (224x144) or vertical (144x224).
            53 => [[224, 144], [144, 224]],

            // Game Boy Advance - Fixed 240x160 TFT LCD.
            5 => [[240, 160]],

            // TIC-80 - Spec defines 240x136.
            65 => [[240, 136]],

            // TI-83 - 96x64 monochrome LCD.
            79 => [[96, 64]],

            // PlayStation Portable - RA docs standard is 320x180.
            // Also accept 480x272 (native LCD resolution, what ppsspp outputs).
            41 => [[320, 180], [480, 272]],

            // Nintendo 3DS - Top screen is 400x240. Bottom screen is 320x240.
            // Supported isolated screens as well as stacked similar to DS.
            62 => [[400, 240], [320, 240], [720, 480]],

            // Virtual Boy - RA docs: 320x186. MobyGames uses 384x224 (raw LED
            // resolution). Classified as handheld (no analog TV output) since
            // it has a fixed-resolution display with no analog video signal.
            28 => [[320, 186], [384, 224]],

            // =================================================================
            // CONSOLES - Analog TV output (CRT). SMPTE 601 also accepted.
            // The first resolution listed is the RA docs editorial standard.
            // PAL variants are included where applicable.
            // =================================================================

            // NES/Famicom - RA docs: 256x224 (NTSC). PAL: 256x240.
            7 => [[256, 224], [256, 240]],

            // Famicom Disk System - Same PPU as NES. NTSC-only region.
            81 => [[256, 224]],

            // SNES/Super Famicom - RA docs: 256x224 (NTSC). PAL: 256x240.
            // 512x224 is the hi-res horizontal mode (Mode 5/6).
            3 => [[256, 224], [256, 240], [512, 224]],

            // Nintendo 64 - RA docs: 320x240.
            2 => [[320, 240]],

            // GameCube - RA docs: 320x240.
            16 => [[320, 240]],

            // Wii - RA docs: 320x240.
            19 => [[320, 240]],

            // Wii U - Digital output. RA docs do not list this system.
            20 => [[1280, 720]],

            // SG-1000 - RA docs: 256x192.
            33 => [[256, 192]],

            // Master System - RA docs: 256x192. Also 256x224 and 256x240
            // used by some titles (Codemasters, etc).
            11 => [[256, 192], [256, 224], [256, 240]],

            // Genesis/Mega Drive - RA docs: 320x224 (NTSC). PAL: 320x240.
            // 256x224 is the H32 mode used by some games (eg: Fire Mustang).
            1 => [[320, 224], [256, 224], [320, 240]],

            // Sega CD - Same VDP as Genesis.
            9 => [[320, 224], [256, 224], [320, 240]],

            // 32X - RA docs: 320x224 (NTSC). Also 256x224 (H32 mode). PAL: 320x240.
            10 => [[320, 224], [256, 224], [320, 240]],

            // Sega Pico - Same VDP as Genesis.
            68 => [[320, 224], [256, 224], [320, 240]],

            // Saturn - VDP2 supports widths 320/352 (lo-res) and 640/704
            // (hi-res), heights 224/240 (NTSC) and 256 (PAL). Interlaced
            // doubles vertical. Most games use 320x224 or 352x224.
            // https://docs.libretro.com/library/beetle_saturn/
            39 => [
                [320, 224], [352, 224], [320, 240], [352, 240],  // lo-res NTSC/PAL
                [320, 256], [352, 256],                          // lo-res PAL
                [640, 224], [704, 224], [640, 240], [704, 240],  // hi-res NTSC/PAL
                [640, 448], [704, 448], [640, 480], [704, 480],  // interlaced
            ],

            // Dreamcast - RA docs: 320x240.
            40 => [[320, 240]],

            // PlayStation - RA docs: 320x240.
            // GPU supports widths 256/320/368/512/640 at 240 lines
            // (progressive) or 480 (interlaced). No 224-line mode
            // exists on PS1 unlike NES/SNES. Most games use 320x240.
            // https://docs.libretro.com/library/beetle_psx/
            // https://psx-spx.consoledev.net/graphicsprocessingunitgpu/
            12 => [
                [320, 240], [256, 240], [368, 240], [512, 240], [640, 240],  // progressive
                [320, 480], [256, 480], [368, 480], [512, 480], [640, 480],  // interlaced
            ],

            // PlayStation 2 - RA docs: 320x240. PCSX2 outputs 640x448 (NTSC)
            // and 640x512 (PAL) at native resolution. "Widescreen" is anamorphic.
            21 => [[320, 240], [640, 448], [640, 512]],

            // Xbox - RA docs: 320x240.
            22 => [[320, 240]],

            // Atari 5200 - RA docs: 320x228. MobyGames: 336x240.
            50 => [[320, 228], [336, 240]],

            // Atari 7800 - RA docs: 320x223 (NTSC). PAL: 320x272.
            // 320x240 and 160x240 (double-wide pixel mode) are also common.
            51 => [[320, 223], [320, 272], [320, 240], [160, 240]],

            // PC Engine/TurboGrafx-16 - RA docs: 256x232. Beetle PCE FAST
            // outputs 256x239, 336x239, or 512x243 depending on the game.
            8 => [[256, 232], [256, 239], [336, 239], [512, 243]],

            // PC Engine CD - Same VDP as PC Engine.
            76 => [[256, 232], [256, 239], [336, 239], [512, 243]],

            // PC-FX - RA docs: 256x232. Core also outputs 256x240 and 341x240.
            49 => [[256, 232], [256, 240], [341, 240]],

            // Neo Geo CD - RA docs: 320x224.
            56 => [[320, 224]],

            // 3DO Interactive Multiplayer - RA docs: 320x240.
            43 => [[320, 240]],

            // Philips CD-i
            42 => [[384, 240], [384, 280]],

            // ColecoVision - RA docs: 256x192.
            44 => [[256, 192]],

            // Intellivision - RA docs: 320x200.
            45 => [[320, 200]],

            // Magnavox Odyssey 2 - RA docs: 320x235.
            23 => [[320, 235]],

            // Fairchild Channel F - RA docs: 306x192. The visible game area
            // is only 102x58, but the FreeChaF core includes overscan in its
            // 306x192 output.
            57 => [[306, 192]],

            // Arcadia 2001 - RA docs: 146x240.
            73 => [[146, 240]],

            // Interton VC 4000 - RA docs: 146x240.
            74 => [[146, 240]],

            // Elektor TV Games Computer - RA docs: 146x240.
            75 => [[146, 240]],

            // Cassette Vision
            54 => [[128, 192]],

            // Super Cassette Vision
            55 => [[256, 192]],

            // Vectrex - RA docs: 193x240. MobyGames: 360x480.
            46 => [[193, 240], [360, 480]],

            // Zeebo - ARM-based console with 800x480 display (digital output).
            70 => [[800, 480]],

            // =================================================================
            // COMPUTERS - Analog TV/monitor output. SMPTE 601 also accepted.
            // =================================================================

            // MSX - RA docs: 272x240.
            29 => [[272, 240]],

            // VIC-20 - RA docs: 200x234.
            34 => [[200, 234]],

            // Atari ST - RA docs: 320x200.
            36 => [[320, 200]],

            // Amstrad CPC - RA docs: 320x226.
            37 => [[320, 226]],

            // Apple II - RA docs: 320x219.
            38 => [[320, 219]],

            // PC-8000/8800 - RA docs: 320x200.
            47 => [[320, 200]],

            // PC-9800 - 640x400 (nearly all PC-98 games).
            48 => [[640, 400]],

            // ZX Spectrum - RA docs do not list; using 320x240 as standard.
            59 => [[320, 240]],

            // Sharp X1
            64 => [[320, 200]],

            // Thomson TO8
            66 => [[672, 432]],

            // =================================================================
            // GAME-DEPENDENT - Left null. No dimension validation at upload.
            // =================================================================

            // Atari 2600 (ID 25) - TIA is 160px wide but vertical scanline
            // count varies per game (~160 to ~230+). Stella core is dynamic.

            // Atari Jaguar (ID 17) - Per RA docs.

            // Atari Jaguar CD (ID 77) - Per RA docs.

            // Arcade (ID 27) - Every board has different hardware.

            // DOS (ID 26) - DOSBox Pure dynamically resizes per video mode.

            // Amiga (ID 35) - PUAE core varies by game mode and PAL/NTSC.

            // Nokia N-Gage (ID 61) - Per RA docs.

            // Sharp X68000 (ID 52) - Per RA docs.

            // Oric (ID 32)

            // ZX81 (ID 31)

            // Commodore 64 (ID 30)

            // Uzebox (ID 80) - Resolution varies per game mode.

            // Game & Watch (ID 60) - gw-libretro uses per-game dimensions.

            // FM Towns (ID 58)

            // PC-6000 (ID 67)

            // =================================================================
            // NON-GAME - Not applicable.
            // =================================================================

            // Hubs (ID 100), Events (ID 101), Standalone (ID 102)
        ];

        foreach ($resolutions as $systemId => $modes) {
            $json = json_encode(array_map(
                fn (array $pair) => ['width' => $pair[0], 'height' => $pair[1]],
                $modes,
            ));

            DB::table('systems')->where('id', $systemId)->update([
                'screenshot_resolutions' => $json,
            ]);
        }

        // Systems with analog TV/monitor output. Upload validation also
        // accepts SMPTE 601 capture resolutions for these systems.
        $analogTvSystems = [
            // Nintendo consoles
            7,   // NES/Famicom
            81,  // Famicom Disk System
            3,   // SNES/Super Famicom
            2,   // Nintendo 64
            16,  // GameCube
            19,  // Wii

            // Sega consoles
            33,  // SG-1000
            11,  // Master System
            1,   // Genesis/Mega Drive
            9,   // Sega CD
            10,  // 32X
            68,  // Sega Pico
            39,  // Saturn
            40,  // Dreamcast

            // Sony consoles
            12,  // PlayStation
            21,  // PlayStation 2

            // Microsoft consoles
            22,  // Xbox

            // Atari consoles
            25,  // Atari 2600
            50,  // Atari 5200
            51,  // Atari 7800
            17,  // Atari Jaguar
            77,  // Atari Jaguar CD

            // NEC consoles
            8,   // PC Engine/TurboGrafx-16
            76,  // PC Engine CD
            49,  // PC-FX

            // SNK consoles
            56,  // Neo Geo CD

            // Other consoles
            43,  // 3DO
            42,  // Philips CD-i
            44,  // ColecoVision
            45,  // Intellivision
            23,  // Magnavox Odyssey 2
            57,  // Fairchild Channel F
            73,  // Arcadia 2001
            74,  // Interton VC 4000
            75,  // Elektor TV Games Computer
            54,  // Cassette Vision
            55,  // Super Cassette Vision

            // Computers with analog output
            29,  // MSX
            34,  // VIC-20
            36,  // Atari ST
            37,  // Amstrad CPC
            38,  // Apple II
            47,  // PC-8000/8800
            59,  // ZX Spectrum
            64,  // Sharp X1
            66,  // Thomson TO8
        ];

        DB::table('systems')->whereIn('id', $analogTvSystems)->update([
            'has_analog_tv_output' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn(['screenshot_resolutions', 'has_analog_tv_output']);
        });
    }
};
