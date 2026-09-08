<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum TicketInboxSectionKind: string
{
    case ToResolve = 'toResolve';
    case AwaitingYourFeedback = 'awaitingYourFeedback';
    case AwaitingReporter = 'awaitingReporter';
    case ReportedOpen = 'reportedOpen';
    case ResolvedByYou = 'resolvedByYou';

    public function needsViewerAction(): bool
    {
        return match ($this) {
            self::ToResolve, self::AwaitingYourFeedback => true,
            default => false,
        };
    }
}
