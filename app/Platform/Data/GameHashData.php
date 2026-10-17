<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\GameHash;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameHash')]
class GameHashData extends Data
{
    public function __construct(
        public int $id,
        public string $md5,
        public ?string $name,
        /** @var GameHashLabelData[] */
        public Lazy|array $labels,
        public Lazy|string|null $patchUrl,
        public Lazy|bool $isMultiDisc,
    ) {
    }

    public static function fromGameHash(GameHash $gameHash): self
    {
        return new self(
            id: $gameHash->id,
            md5: $gameHash->md5,
            name: $gameHash->name,
            labels: Lazy::create(fn () => GameHashLabelData::fromLabelsString($gameHash->labels)),
            patchUrl: Lazy::create(fn () => $gameHash->patch_url),
            isMultiDisc: Lazy::create(fn () => $gameHash->isMultiDiscGameHash()),
        );
    }

    /**
     * @param Collection<int, GameHash> $gameHashes
     * @return GameHashData[]
     */
    public static function fromCollection(Collection $gameHashes): array
    {
        return array_values(array_map(
            fn ($gameHash) => self::fromGameHash($gameHash),
            $gameHashes->all()
        ));
    }
}
