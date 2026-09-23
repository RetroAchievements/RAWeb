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
    case ReportedByYou = 'reportedByYou';
    case ResolvedByYou = 'resolvedByYou';

    public function sortColumn(): string
    {
        return match ($this) {
            self::ResolvedByYou => 'resolved_at',
            default => 'created_at',
        };
    }

    public function needsViewerAction(): bool
    {
        return match ($this) {
            self::ToResolve, self::AwaitingYourFeedback => true,
            default => false,
        };
    }
}
