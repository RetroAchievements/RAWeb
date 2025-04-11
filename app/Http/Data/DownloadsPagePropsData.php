<?php

declare(strict_types=1);

namespace App\Http\Data;

use App\Platform\Data\EmulatorData;
use App\Platform\Data\PlatformData;
use App\Platform\Data\SystemData;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('DownloadsPageProps')]
class DownloadsPagePropsData extends Data
{
    /**
     * @param Collection<int, EmulatorData> $allEmulators
     * @param Collection<int, PlatformData> $allPlatforms
     * @param Collection<int, SystemData> $allSystems
     */
    public function __construct(
        public Collection $allEmulators,
        public Collection $allPlatforms,
        public Collection $allSystems,
        /** @var int[] $topSystemIds */
        public array $topSystemIds,
        #[LiteralTypeScriptType('number[][]')]
        public array $popularEmulatorsBySystem,
    ) {
    }
}
