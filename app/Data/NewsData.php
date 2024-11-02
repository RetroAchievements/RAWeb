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
        public Carbon $timestamp,
        public string $title,
        public ?string $lead,
        public string $payload,
        public UserData $user,
        public ?string $link,
        public ?string $image,
    ) {
    }

    public static function fromNews(News $news): self
    {
        return new self(
            id: $news->ID,
            timestamp: Carbon::parse($news->Timestamp),
            title: $news->Title,
            lead: $news->lead,
            payload: $news->Payload,
            user: UserData::from($news->user),
            link: $news->Link,
            image: $news->Image,
        );
    }
}
