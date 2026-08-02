<?php

declare(strict_types=1);

namespace App\Observers;

use App\Community\Services\WatchedTermMatcher;
use App\Models\Comment;
use App\Models\ForumTopicComment;
use App\Models\User;
use App\Support\Alerts\WatchedTermAlert;
use Illuminate\Support\Facades\Log;
use Throwable;

class ForumTopicCommentObserver
{
    public function __construct(
        private readonly WatchedTermMatcher $watchedTermMatcher,
    ) {
    }

    public function created(ForumTopicComment $forumTopicComment): void
    {
        if ($this->isAutomated($forumTopicComment)) {
            return;
        }

        $this->alertOnTerms(
            $forumTopicComment,
            $this->watchedTermMatcher->findMatches($forumTopicComment->body ?? ''),
        );
    }

    /**
     * A clean post can have a watched term edited into it later.
     * We need to detect that too.
     */
    public function updated(ForumTopicComment $forumTopicComment): void
    {
        if (!$forumTopicComment->wasChanged('body') || $this->isAutomated($forumTopicComment)) {
            return;
        }

        $matchedTerms = $this->watchedTermMatcher->findMatches($forumTopicComment->body ?? '');
        if ($matchedTerms === []) {
            return;
        }

        $previousBody = $forumTopicComment->getOriginal('body') ?? '';

        $newlyMatchedTerms = array_values(array_diff(
            $matchedTerms,
            $this->watchedTermMatcher->findMatches($previousBody),
        ));

        $this->alertOnTerms($forumTopicComment, $newlyMatchedTerms);
    }

    private function isAutomated(ForumTopicComment $forumTopicComment): bool
    {
        return $forumTopicComment->author_id === Comment::SYSTEM_USER_ID;
    }

    /**
     * @param list<string> $matchedTerms
     */
    private function alertOnTerms(ForumTopicComment $forumTopicComment, array $matchedTerms): void
    {
        if ($matchedTerms === []) {
            return;
        }

        $responsibleUser = $this->responsibleUser($forumTopicComment);
        if (!$responsibleUser) {
            return;
        }

        $this->raiseAlert(new WatchedTermAlert(
            user: $responsibleUser,
            matchedTerms: $matchedTerms,
            location: 'a forum post',
            destinationUrl: route('forum-topic-comment.show', ['comment' => $forumTopicComment]),
        ), $forumTopicComment->id);
    }

    private function responsibleUser(ForumTopicComment $forumTopicComment): ?User
    {
        return $forumTopicComment->editedBy ?? $forumTopicComment->sentBy ?? $forumTopicComment->user;
    }

    private function raiseAlert(WatchedTermAlert $alert, int $recordId): void
    {
        try {
            if (!$alert->send()) {
                Log::warning('Watched term matched but no alert webhook is configured.', [
                    'forum_topic_comment_id' => $recordId,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Failed to raise a watched term alert.', [
                'forum_topic_comment_id' => $recordId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
