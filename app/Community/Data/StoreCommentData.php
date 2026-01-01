<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Enums\CommentableType;
use App\Community\Requests\StoreCommentRequest;
use Spatie\LaravelData\Data;

class StoreCommentData extends Data
{
    public function __construct(
        public string $body,
        public int $commentableId,
        public CommentableType $commentableType,
    ) {
    }

    public static function fromRequest(StoreCommentRequest $request): self
    {
        // React components send the string enum value (eg: "game.comment").
        $commentableType = CommentableType::from($request->commentableType);

        return new self(
            body: $request->body,
            commentableId: (int) $request->commentableId,
            commentableType: $commentableType,
        );
    }
}
