<?php

declare(strict_types=1);

namespace App\Observers;

use App\Community\Services\WatchedTermMatcher;
use App\Models\Comment;
use App\Support\Alerts\WatchedTermAlert;
use Illuminate\Support\Facades\Log;
use Throwable;

class CommentObserver
{
    public function __construct(
        private readonly WatchedTermMatcher $watchedTermMatcher,
    ) {
    }

    /**
     * Comments have no update path, so create time is the only check. If comment
     * editing is ever added, this observer must handle the update event too.
     */
    public function created(Comment $comment): void
    {
        if ($comment->is_automated || $comment->commentable_type->isManagementComment()) {
            return;
        }

        $matchedTerms = $this->watchedTermMatcher->findMatches($comment->body ?? '');
        if ($matchedTerms === []) {
            return;
        }

        $this->raiseAlert(new WatchedTermAlert(
            user: $comment->user,
            matchedTerms: $matchedTerms,
            location: sprintf('a %s comment', mb_strtolower($comment->commentable_type->label())),
            destinationUrl: $comment->url,
        ), $comment->id);
    }

    private function raiseAlert(WatchedTermAlert $alert, int $recordId): void
    {
        try {
            if (!$alert->send()) {
                Log::warning('Watched term matched but no alert webhook is configured.', [
                    'comment_id' => $recordId,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Failed to raise a watched term alert.', [
                'comment_id' => $recordId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
