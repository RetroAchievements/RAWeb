<?php

declare(strict_types=1);

namespace App\Community\Services;

use App\Models\ForumTopic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForumTopicPageService
{
    public function buildViewData(Request $request, ForumTopic $forumTopic): array
    {
        $validatedData = $request->validate([
            "page.number" => "sometimes|integer|min:1",
            "offset" => "sometimes|integer|min:0",
            "comment" => "sometimes|integer|min:1",
        ]);

        $user = Auth::user();
        $isSubscribed = $user ? isUserSubscribedToForumTopic($forumTopic->id, $user->id) : false;

        $commentsPerPage = 15;
        $targetCommentId = isset($validatedData["comment"]) ? (int) $validatedData["comment"] : null;

        $currentPage = (int) ($validatedData["page"]["number"] ?? 1);
        $offset = 0;
        if (isset($validatedData['offset'])) {
            $offset = (int) $validatedData['offset'];
            $currentPage = (int) ceil(($offset + 1) / $commentsPerPage);
        } else {
            $offset = ($currentPage - 1) * $commentsPerPage;
        }

        if ($targetCommentId) {
            // Override $offset, just find this comment and go to it.
            getTopicCommentCommentOffset(
                $forumTopic->id,
                $targetCommentId,
                $commentsPerPage,
                $offset
            );
        }

        $allForumTopicCommentsForTopic = $forumTopic
            ->comments()
            ->with(["user", "forumTopic"])
            ->where("ForumTopicID", $forumTopic->id)
            ->orderBy("DateCreated", "asc")
            ->offset($offset)
            ->limit($commentsPerPage)
            ->get();

        return [
            "allForumTopicCommentsForTopic" => $allForumTopicCommentsForTopic,
            "category" => $forumTopic->forum->category,
            "commentsPerPage" => $commentsPerPage,
            "currentPage" => $currentPage,
            "forum" => $forumTopic->forum,
            "forumTopic" => $forumTopic,
            "isSubscribed" => $isSubscribed,
            "offset" => $offset,
            "targetCommentId" => $targetCommentId,
            "user" => $user,
        ];
    }
}
