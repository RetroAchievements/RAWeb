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
     * @param Collection<int, UserData> $initialSupporters
     * @param DeferProp|Collection<int, UserData> $deferredSupporters
     */
    public function __construct(
        public Collection $recentSupporters,
        public Collection $initialSupporters,
        public DeferProp|Collection $deferredSupporters,
        public int $totalCount,
    ) {
    }
}
