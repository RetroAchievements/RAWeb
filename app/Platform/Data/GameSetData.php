<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\GameSet;
use App\Platform\Enums\GameSetType;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameSet')]
class GameSetData extends Data
{
    public function __construct(
        public int $id,
        public GameSetType $type,
        public ?string $title,
        public ?string $badgeUrl,
        public int $gameCount,
        public int $linkCount,
        public Carbon $updatedAt,
        public Lazy|int|null $forumTopicId,
    ) {
    }

    /**
     * Creates a DTO from a GameSet model with eager loaded counts.
     */
    public static function fromGameSetWithCounts(GameSet $gameSet): self
    {
        return new self(
            id: $gameSet->id,
            type: $gameSet->type,
            title: $gameSet->title,
            badgeUrl: media_asset($gameSet->image_asset_path),
            gameCount: $gameSet->games_count ?? 0,
            linkCount: $gameSet->link_count ?? 0,
            updatedAt: $gameSet->updated_at,
            forumTopicId: Lazy::create(fn () => $gameSet->forum_topic_id),
        );
    }
}
