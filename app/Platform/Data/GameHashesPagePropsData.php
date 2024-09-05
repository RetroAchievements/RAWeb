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
     */
    public function __construct(
        public GameData $game,
        public array $hashes,
        public UserPermissionsData $can,
    ) {
    }
}
