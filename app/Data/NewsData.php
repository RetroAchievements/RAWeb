<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\News;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('News')]
class NewsData extends Data
{
    public function __construct(
        public int $id,
        public Carbon $createdAt,
        public string $title,
        public ?string $lead,
        public string $body,
        public UserData $user,
        public ?string $link,
        public ?string $imageAssetPath,
        public ?Carbon $publishAt,
        public ?Carbon $unpublishAt,
        public ?Carbon $pinnedAt,
    ) {
    }

    public static function fromNews(News $news): self
    {
        return new self(
            id: $news->id,
            createdAt: $news->created_at,
            title: $news->title,
            lead: $news->lead,
            body: $news->body,
            user: UserData::from($news->user),
            link: $news->link,
            imageAssetPath: $news->image_asset_path,
            publishAt: $news->publish_at,
            unpublishAt: $news->unpublish_at,
            pinnedAt: $news->pinned_at,
        );
    }
}
