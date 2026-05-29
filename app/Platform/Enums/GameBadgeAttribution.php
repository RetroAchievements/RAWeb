<?php

declare(strict_types=1);

namespace App\Platform\Enums;

enum GameBadgeAttribution: string
{
    case Live = 'live';
    case BackfillAuditLog = 'backfill_audit_log';
    case BackfillCommentHeuristic = 'backfill_comment_heuristic';
    case BackfillCurrentCanonical = 'backfill_current_canonical';
    case BackfillForumComment = 'backfill_forum_comment';
}
