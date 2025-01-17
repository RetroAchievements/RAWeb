<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Forum;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Forum')]
class ForumData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public Lazy|string $description,
        public Lazy|int $orderColumn,
        public Lazy|ForumCategoryData $category,
    ) {
    }

    public static function fromForum(Forum $forum): self
    {
        return new self(
            id: $forum->id,
            title: $forum->title,
            description: Lazy::create(fn () => $forum->description),
            orderColumn: Lazy::create(fn () => $forum->order_column),
            category: Lazy::create(fn () => ForumCategoryData::fromForumCategory($forum->category)),
        );
    }
}
