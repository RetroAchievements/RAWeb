<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use Illuminate\Support\Collection;
use Inertia\DeferProp;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('PatreonSupportersPageProps')]
class PatreonSupportersPagePropsData extends Data
{
    /**
     * @param Collection<int, UserData> $recentSupporters
     * @param Collection<int, UserData> $initialTier2Supporters
     * @param DeferProp|Collection<int, UserData> $deferredTier2Supporters
     * @param Collection<int, UserData> $initialTier1Supporters
     * @param DeferProp|Collection<int, UserData> $deferredTier1Supporters
     */
    public function __construct(
        public Collection $recentSupporters,
        public Collection $initialTier2Supporters,
        public DeferProp|Collection $deferredTier2Supporters,
        public int $tier2Count,
        public Collection $initialTier1Supporters,
        public DeferProp|Collection $deferredTier1Supporters,
        public int $tier1Count,
    ) {
    }
}
