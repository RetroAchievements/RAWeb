<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum ModerationActionType: string
{
    case Mute = 'mute';
    case Unmute = 'unmute';
    case Ban = 'ban';
    case Unban = 'unban';
    case Unrank = 'unrank';
    case Rerank = 'rerank';

    public function label(): string
    {
        return match ($this) {
            self::Mute => 'Muted',
            self::Unmute => 'Unmuted',
            self::Ban => 'Banned',
            self::Unban => 'Unbanned',
            self::Unrank => 'Unranked',
            self::Rerank => 'Reranked',
        };
    }
}
