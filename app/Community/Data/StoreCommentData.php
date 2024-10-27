<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Community\Requests\StoreCommentRequest;
use Spatie\LaravelData\Data;

class StoreCommentData extends Data
{
    public function __construct(
        public string $body,
        public int $commentableId,
        public int $commentableType,
    ) {
    }

    public static function fromRequest(StoreCommentRequest $request): self
    {
        return new self(
            body: $request->body,
            commentableId: (int) $request->commentableId,
            commentableType: (int) $request->commentableType,
        );
    }
}
