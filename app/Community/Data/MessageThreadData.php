<?php

declare(strict_types=1);

namespace App\Community\Data;

use App\Data\UserData;
use App\Models\MessageThread;
use Illuminate\Database\Eloquent\Collection;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('MessageThread')]
class MessageThreadData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public int $numMessages,
        public Lazy|MessageData $lastMessage,
        public bool $isUnread,
        /** @var MessageData[] */
        public Lazy|array $messages = [],
        /** @var UserData[] */
        public Lazy|array $participants = [],
    ) {
    }

    public static function fromMessageThread(MessageThread $messageThread): self
    {
        return new self(
            id: $messageThread->id,
            title: $messageThread->title,
            numMessages: $messageThread->num_messages,
            lastMessage: Lazy::create(fn () => MessageData::fromMessage($messageThread->lastMessage)),
            isUnread: $messageThread->is_unread,

            /** @phpstan-ignore-next-line -- all() is valid */
            messages: Lazy::create(fn () => $messageThread->messages->map(
                fn ($message) => MessageData::fromMessage($message)
            ))->all(),

            /** @phpstan-ignore-next-line -- all() is valid */
            participants: Lazy::create(fn () => $messageThread->participants->map(
                fn ($participant) => UserData::fromUser($participant)->include('deletedAt')
            ))->all(),
        );
    }

    /**
     * @param Collection<int, MessageThread> $messageThreads
     * @return array<MessageThreadData>
     */
    public static function fromCollection(Collection $messageThreads): array
    {
        return array_map(
            fn ($messageThread) => self::fromMessageThread($messageThread)->include('lastMessage'),
            $messageThreads->all()
        );
    }
}
