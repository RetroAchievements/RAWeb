<?php

declare(strict_types=1);

namespace App\Community\Enums;

use App\Models\Comment;
use App\Models\ForumTopicComment;
use App\Models\Message;
use App\Models\PlayerGame;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ModerationReportableType: string
{
    case Comment = 'Comment';
    case DirectMessage = 'DirectMessage';
    case ForumTopicComment = 'ForumTopicComment';
    case UserProfile = 'UserProfile';
    case PlayerBeatTime = 'PlayerBeatTime';

    public function getModelClass(): string
    {
        return match ($this) {
            self::Comment => Comment::class,
            self::DirectMessage => Message::class,
            self::ForumTopicComment => ForumTopicComment::class,
            self::UserProfile => User::class,
            self::PlayerBeatTime => PlayerGame::class,
        };
    }

    public function getReportedItem(int $id): ?Model
    {
        $modelClass = $this->getModelClass();

        return $modelClass::find($id);
    }
}
