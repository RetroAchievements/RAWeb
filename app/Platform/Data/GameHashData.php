<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\GameHash;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript('GameHash')]
class GameHashData extends Data
{
    public function __construct(
        public int $id,
        public string $md5,
        public ?string $name,
        #[TypeScriptType('App\\Platform\\Data\\GameHashLabelData[]')]
        public array $labels,
        public ?string $patchUrl,
    ) {
    }

    public static function fromGameHash(GameHash $gameHash): self
    {
        return new self(
            id: $gameHash->id,
            md5: $gameHash->md5,
            name: $gameHash->name,
            labels: GameHashLabelData::fromLabelsString($gameHash->labels),
            patchUrl: $gameHash->patch_url,
        );
    }

    /**
     * @param Collection<int, GameHash> $gameHashes
     * @return GameHashData[]
     */
    public static function fromCollection(Collection $gameHashes): array
    {
        return array_map(
            fn ($gameHash) => self::fromGameHash($gameHash),
            $gameHashes->all()
        );
    }
}
