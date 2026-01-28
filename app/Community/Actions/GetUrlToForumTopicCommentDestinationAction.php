<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use Illuminate\Support\Facades\Auth;

class GetUrlToForumTopicCommentDestinationAction
{
    public function execute(ForumTopicComment $comment): string
    {
        $forumTopic = $comment->forumTopic;

        abort_if($forumTopic === null, 404);

        $page = $this->calculateCommentPage($comment);
        $hashAnchor = "#{$comment->id}";

        // If on page 1, don't include any query params - just the anchor.
        if ($page === 1) {
            return route('forum-topic.show', ['topic' => $forumTopic]) . $hashAnchor;
        }

        // For page 2+, use the comment query param so the page auto-calculates the correct page.
        return route('forum-topic.show', ['topic' => $forumTopic, 'comment' => $comment->id]) . $hashAnchor;
    }

    private function calculateCommentPage(ForumTopicComment $comment): int
    {
        $currentUser = Auth::user();

        // Comments are displayed in ascending order by created_at (oldest first).
        // Count how many comments come before this one.
        $positionFromStart = $comment->forumTopic->visibleComments($currentUser)
            ->where('created_at', '<', $comment->created_at)
            ->count();

        // Position is 0-indexed, so add 1 for the comment itself.
        return (int) ceil(($positionFromStart + 1) / ForumTopic::COMMENTS_PER_PAGE);
    }
}
