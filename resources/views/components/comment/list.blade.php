@props([
    'articleType' => 0,
    'articleId' => 0,
    'embedded' => true,
])

@php

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Comment;

$perPage = $embedded ? 20 : 50;

$comments = Comment::where('ArticleType', $articleType)
    ->where('ArticleID', $articleId)
    ->with(['user' => fn($q) => $q->withTrashed()])
    ->orderBy('Submitted');

$totalComments = $comments->count();
$totalPages = floor(($totalComments + $perPage - 1) / $perPage);

if ($embedded) {
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
    $subjectType = SubscriptionSubjectType::fromArticleType($articleType);
    if ($subjectType !== null) {
        $canSubscribe = true;
        $isSubscribed = isUserSubscribedToArticleComments($articleType, $articleId, $user->id);
    }
}

$route = match($articleType) {
    ArticleType::Achievement => route('achievement.comments', $articleId),
    ArticleType::Leaderboard => route('leaderboard.comments', $articleId),
    default => '',
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
            @elseif ($totalComments > count($comments))
                <div class="hidden sm:block">
                    Recent comments: <span class="smalltext">(<a href="{{ $route }}">All {{ $totalComments }}</a>)</span>
                </div>

                <div class="sm:hidden">
                    <p>Recent comments</p>
                    <p class="smalltext">(<a href="{{ $route }}">See all {{ $totalComments }}</a>)</p>
                </div>
            @else
                Comments:
            @endif
        </div>

        @if ($canSubscribe)
            <x-update-subscription-button
                :subjectType="$subjectType"
                :subjectId="$articleId"
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
                    :when="$comment->Submitted"
                    :payload="nl2br($comment->Payload)"
                    :articleType="$articleType"
                    :articleId="$articleId"
                    :commentId="$comment->ID"
                    :allowDelete="$user->can('delete', [App\Models\Comment::class, $comment])"
                />
            @endforeach

            <x-comment.input-row articleType="{{ $articleType }}" articleId="{{ $articleId }}" />
        </tbody>
    </table>

    @if (!$embedded && $totalPages > 1)
        <div class="w-full flex items-center mt-2">
            <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
        </div>
    @endif

</div>