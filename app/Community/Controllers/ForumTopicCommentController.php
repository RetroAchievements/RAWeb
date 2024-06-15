<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\AddCommentAction;
use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Community\Requests\ForumTopicCommentRequest;
use App\Community\Services\ForumRecentPostsPageService;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Support\Shortcode\Shortcode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ForumTopicCommentController extends CommentController
{
    public function __construct(
        protected ForumRecentPostsPageService $recentPostsPageService
    ) {
    }

    /**
     * There is no create form for creating a new comment.
     * comments have to be created for something -> use sub resource create route, e.g.
     * - user.comment.create (wall)
     * - achievement-ticket.comment.create
     * - forum-topic-comment.create
     */
    public function create(): void
    {
    }

    public function store(
        ForumTopicCommentRequest $request,
        ForumTopic $topic,
        AddCommentAction $addCommentAction,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('create', [ForumTopicComment::class, $topic]);

        // TODO replace with ForumTopicComment, not a commentable morph anymore
        // $comment = $addCommentAction->execute($request, $topic);

        // if (!$comment) {
        return back()->with('error', $this->resourceActionErrorMessage('topic.comment', 'create'));
        // }

        // return redirect($getUrlToCommentDestinationAction->execute($comment))
        //     ->with('success', $this->resourceActionSuccessMessage('comment', 'create'));
    }

    public function edit(ForumTopicComment $comment): View
    {
        $this->authorize('update', $comment);

        return view('forum-topic-comment.edit')
            ->with('comment', $comment);
    }

    protected function update(
        ForumTopicCommentRequest $request,
        ForumTopicComment $comment,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('update', $comment);

        $comment->fill($request->validated())->save();

        // TODO replace with similar logic for ForumTopicComment, not a commentable morph anymore
        return back();
        // return redirect($getUrlToCommentDestinationAction->execute($comment))
        //     ->with('success', $this->resourceActionSuccessMessage('comment', 'update'));
    }

    protected function destroy(ForumTopicComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $return = $comment->commentable->canonicalUrl;

        /*
         * don't touch
         */
        $comment->timestamps = false;

        $comment->delete();

        return redirect($return)
            ->with('success', $this->resourceActionSuccessMessage('comment', 'delete'));
    }

    public function showRecentPosts(): InertiaResponse
    {
        $this->authorize('viewAny', ForumTopicComment::class);

        // TODO after POC: thin this out
        $pageProps = $this->recentPostsPageService->buildViewData(
            request()->user(),
            (int) request()->input('offset', 0)
        );

        // TODO after POC: migrate the query to Eloquent ORM and have it do these mappings automatically
        $mappedRecentForumPosts = [];
        foreach ($pageProps['recentForumPosts'] as &$recentForumPost) {
            $mappedRecentForumPost = [];

            $mappedRecentForumPost['forumTopicId'] = (int) $recentForumPost['ForumTopicID'];
            $mappedRecentForumPost['forumTopicTitle'] = $recentForumPost['ForumTopicTitle'];
            $mappedRecentForumPost['commentId'] = (int) $recentForumPost['CommentID'];
            $mappedRecentForumPost['postedAt'] = $recentForumPost['PostedAt'];
            $mappedRecentForumPost['authorDisplayName'] = $recentForumPost['Author'];
            $mappedRecentForumPost['shortMessage'] = Shortcode::stripAndClamp($recentForumPost['ShortMsg'], 999);
            $mappedRecentForumPost['commentIdDay'] = isset($recentForumPost['CommentID_1d']) ? (int) $recentForumPost['CommentID_1d'] : null;
            $mappedRecentForumPost['commentCountDay'] = isset($recentForumPost['Count_1d']) ? (int) $recentForumPost['Count_1d'] : null;
            $mappedRecentForumPost['commentIdWeek'] = isset($recentForumPost['CommentID_7d']) ? (int) $recentForumPost['CommentID_7d'] : null;
            $mappedRecentForumPost['commentCountWeek'] = isset($recentForumPost['Count_7d']) ? (int) $recentForumPost['Count_7d'] : null;

            $mappedRecentForumPosts[] = $mappedRecentForumPost;
        }
        unset($pageProps['recentForumPosts']);
        $pageProps['recentForumPosts'] = $mappedRecentForumPosts;

        // TODO after POC: remove this code
        if (isset($pageProps['previousPageUrl'])) {
            $pageProps['previousPageUrl'] = str_replace('recent-posts', 'recent-posts2', $pageProps['previousPageUrl']);
        }
        if (isset($pageProps['nextPageUrl'])) {
            $pageProps['nextPageUrl'] = str_replace('recent-posts', 'recent-posts2', $pageProps['nextPageUrl']);
        }

        return Inertia::render('forums/recent-posts', $pageProps);
    }
}
