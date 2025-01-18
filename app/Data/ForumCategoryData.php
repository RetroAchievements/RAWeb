<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\ForumCategory;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('ForumCategory')]
class ForumCategoryData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public Lazy|string $description,
        public Lazy|int $orderColumn,
    ) {
    }

    public static function fromForumCategory(ForumCategory $category): self
    {
        return new self(
            id: $category->id,
            title: $category->title,
            description: Lazy::create(fn () => $category->description),
            orderColumn: Lazy::create(fn () => $category->order_column),
        );
    }
}
