@props([
    'articleType' => 0,
    'articleId' => 0,
    'article' => null,
])

@php

$commentId = "art_{$articleType}_{$articleId}";
$canCreate = false;

$user = request()->user();
if ($user) {
    if ($article) {
        $canCreate = $user->can('create', [App\Models\Comment::class, $article, $articleType]);
    } else {
        $canCreate = $user->can('create', [App\Models\Comment::class]);
    }
}

@endphp

@if ($canCreate)
    <tr id="comment_$commentId" class="do-not-highlight">
        <td class="align-top pb-3">
            {!! userAvatar($user, label: false) !!}
        </td>
        <td class="w-full pb-3" colspan="3">
            <form action="/request/comment/create.php" method="post">
                {!! csrf_field() !!}
                <input type="hidden" name="commentable_id" value="{{ $articleId }}">
                <input type="hidden" name="commentable_type" value="{{ $articleType }}">
                <div x-data="{ isValid: true }" class="flex align-center mb-1">
                    <textarea
                        class="comment-textarea"
                        name="body"
                        maxlength="2000"
                        placeholder="Enter a comment here..."
                        id="comment_textarea_{{ $commentId }}"
                        x-on:input="autoExpandTextInput($el); isValid = window.getStringByteCount($event.target.value) <= 2000;"
                    ></textarea>
                    <button class="btn h-9 ml-2" :disabled="!isValid" aria-label="Post comment" title="Post comment">
                        Submit
                    </button>
                </div>
                <div class="textarea-counter" data-textarea-id="comment_textarea_{{ $commentId }}"></div>
                <div class="text-danger hidden"></div>
            </form>
        </td>
    </tr>
@elseif ($user?->is_muted)
    <tr id="comment_$commentId" class="do-not-highlight">
        <td class="w-full p-0 pb-3 bg-red" colspan="4">
            <div class="bg-embed p-2 rounded-b-lg">
                <p class="text-center text-muted">You are muted until {{ getNiceDate($user->muted_until->timestamp) }}.</p>
            </div>
        </td>
    </tr>
@endif