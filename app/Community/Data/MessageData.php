<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use App\Models\Message;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('Message')]
class MessageData extends Data
{
    public function __construct(
        public string $body,
        public Carbon $createdAt,
        public Lazy|UserData $author,
        public Lazy|UserData|null $sentBy,
    ) {
    }

    public static function fromMessage(Message $message): self
    {
        return new self(
            body: $message->body,
            createdAt: $message->created_at,
            author: Lazy::create(fn () => UserData::fromUser($message->author)->include('deletedAt')),
            sentBy: $message->sent_by_id
                ? Lazy::create(fn () => UserData::fromUser($message->sentBy)->include('deletedAt'))
                : null,
        );
    }
}
