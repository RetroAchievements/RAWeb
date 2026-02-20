<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum AchievementChangelogEntryType: string
{
    case Created = 'created';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Edited = 'edited';
    case Promoted = 'promoted';
    case Demoted = 'demoted';
    case DescriptionUpdated = 'description-updated';
    case TitleUpdated = 'title-updated';
    case PointsChanged = 'points-changed';
    case BadgeUpdated = 'badge-updated';
    case EmbedUrlUpdated = 'embed-url-updated';
    case LogicUpdated = 'logic-updated';
    case MovedToDifferentGame = 'moved-to-different-game';
    case TypeSet = 'type-set';
    case TypeChanged = 'type-changed';
    case TypeRemoved = 'type-removed';
}
