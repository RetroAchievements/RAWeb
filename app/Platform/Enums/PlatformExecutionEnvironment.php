<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum PlatformExecutionEnvironment: string
{
    /** Windows, macOS, Linux */
    case Desktop = 'desktop';

    /** Android, iOS */
    case Mobile = 'mobile';

    /** Xbox, PlayStation, Switch */
    case Console = 'console';

    /** Raspberry Pi, similar SBCs */
    case SingleBoard = 'single_board';

    /** SD2SNES, NES cartridge adapter */
    case OriginalHardware = 'original_hardware';

    /** Custom embedded systems (specialized emulation hardware like mini-consoles, FPGA) */
    case Embedded = 'embedded';

    /** Browser-based */
    case Web = 'web';

    public function label(): string
    {
        return match ($this) {
            self::Desktop => 'Desktop',
            self::Mobile => 'Mobile',
            self::Console => 'Console',
            self::SingleBoard => 'Single Board Computer',
            self::OriginalHardware => 'Original Hardware',
            self::Embedded => 'Embedded System',
            self::Web => 'Web',
        };
    }
}
