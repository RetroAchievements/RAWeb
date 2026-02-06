@props([
    'commentableType' => null,
    'commentableId' => 0,
    'article' => null,
    'embedded' => true,
    'showAll' => false,
])

@php

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Models\Comment;
use App\Models\User;

$perPage = $embedded ? 20 : 50;

$comments = Comment::where('commentable_type', $commentableType)
    ->where('commentable_id', $commentableId)
    ->with(['user' => fn($q) => $q->withTrashed()])
    ->orderBy('created_at');

$totalComments = $comments->count();
$totalPages = floor(($totalComments + $perPage - 1) / $perPage);

if ($showAll) {
    $comments = $comments->get();
} elseif ($embedded) {
    // in embedded mode, return the $perPage most recent comments
    if ($totalComments > $perPage) {
        $comments = $comments->offset($totalComments - $perPage)->limit($perPage)->get();
    } else {
        $comments = $comments->get();
    }
} else {
    // return the comments for the current page
    $validatedData = request()->validate([
        'page.number' => 'sometimes|integer|min:1',
    ]);
    $currentPage = (int) ($validatedData['page']['number'] ?? 1);

    $comments = $comments->offset(($currentPage - 1) * $perPage)->limit($perPage)->get();
}

$count = count($comments);

$canSubscribe = false;

$user = request()->user();
if ($user) {
    $subjectType = SubscriptionSubjectType::fromCommentableType($commentableType);
    if ($subjectType !== null) {
        $canSubscribe = true;
        $service = new SubscriptionService();
        $isSubscribed = $service->isSubscribed($user, $subjectType, $commentableId);
    }
}

$route = match($commentableType) {
    CommentableType::Game => route('game.comment.index', ['game' => $commentableId]),
    CommentableType::GameModification => route('game.modification-comment.index', ['game' => $commentableId]),
    CommentableType::GameHash => route('game.hashes.comment.index', ['game' => $commentableId]),
    CommentableType::SetClaim => route('game.claims.comment.index', ['game' => $commentableId]),
    CommentableType::Achievement => route('achievement.comment.index', ['achievement' => $commentableId]),
    CommentableType::Leaderboard => route('leaderboard.comment.index', ['leaderboard' => $commentableId]),
    CommentableType::User => route('user.comment.index', ['user' => $article ?? User::find($commentableId)]),
    CommentableType::UserModeration => null,
    default => 'unsupported type ' . $commentableType?->value,
};

@endphp

<div class="commentscomponent">
    <div class="flex justify-between items-center mb-3">
        <div>
            @if ($totalComments == 0)
                <i>No comments</i>
            @elseif (!$embedded)
                @if ($totalPages > 1)
                    <div class="w-full flex items-center">
                        <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
                    </div>
                @endif
            @elseif ($totalComments > count($comments) && $route)
                <div class="hidden sm:block">
                    Recent comments: <span class="smalltext">(<a href="{{ $route }}">All {{ $totalComments }}</a>)</span>
                </div>

                <div class="sm:hidden">
                    <p>Recent comments</p>
                    <p class="smalltext">(<a href="{{ $route }}">See all {{ $totalComments }}</a>)</p>
                </div>
            @elseif ($totalComments > count($comments))
                Recent comments:
            @else
                Comments:
            @endif
        </div>

        @if ($canSubscribe)
            <x-update-subscription-button
                :subjectType="$subjectType"
                :subjectId="$commentableId"
                :isSubscribed="$isSubscribed"
                resource="{{ $embedded ? 'comments' : null }}"
            />
        @endif
    </div>

    <table id="feed" class="table-highlight">
        <tbody>
            @foreach ($comments as $comment)
                <x-comment.item
                    :author="$comment->user"
                    :when="$comment->created_at"
                    :payload="nl2br($comment->body)"
                    :commentableType="$commentableType"
                    :commentableId="$commentableId"
                    :commentId="$comment->id"
                    :allowDelete="$user?->can('delete', [App\Models\Comment::class, $comment])"
                />
            @endforeach

            <x-comment.input-row :commentableType="$commentableType" :commentableId="$commentableId" :article="$article" />
        </tbody>
    </table>

    @if (!$embedded && $totalPages > 1)
        <div class="w-full flex items-center mt-2">
            <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
        </div>
    @endif
</div>