<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Data\UserPermissionsData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameHashesPageProps')]
class GameHashesPagePropsData extends Data
{
    /**
     * @param GameHashData[] $hashes
     * @param GameHashData[] $incompatibleHashes
     * @param GameHashData[] $untestedHashes
     * @param GameHashData[] $patchRequiredHashes
     */
    public function __construct(
        public GameData $game,
        public array $hashes,
        public array $incompatibleHashes,
        public array $untestedHashes,
        public array $patchRequiredHashes,
        public UserPermissionsData $can,
    ) {
    }
}
