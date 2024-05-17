@props([
    'author' => null, // ?User
    'when' => null, // ?Carbon
    'payload' => '',
    'articleType' => 0,
    'articleId' => 0,
    'commentId' => 0,
    'allowDelete' => false,
])

@if ($author && $author->User === 'Server')
    <tr class="comment system">
        <td class="align-top py-2">
            @if ($commentId > 0)
                <div class="relative">
                    <div class="absolute h-px w-px left-0" style="top: -74px;" id="comment_{{ $commentId }}"></div>
                </div>
            @endif
        </td>
        <td class="w-full py-2" colspan="3">
            <div>
                <span class="smalldate">{{ $when?->format('j M Y H:i') }}</span>
            </div>

            <div style="word-break: break-word">
                {!! $payload !!}
            </div>
        </td>
    </tr>
@elseif ($author && $author->banned_at && !request()->user()?->can('manage', $author))
    {{-- banned user comments are only visible to moderators --}}
@else
    <tr class="comment" @if ($commentId > 0) id="comment_{{ $commentId }}_highlight" @endif>
        <td class="align-top py-2">
            @if ($commentId > 0)
                <div class="relative">
                    <div class="absolute h-px w-px left-0" style="top: -74px;" id="comment_{{ $commentId }}"></div>
                </div>
            @endif
            {!! userAvatar($author ?? 'Deleted User', label: false) !!}
        </td>
        <td class="w-full py-2" colspan="3">
            @if ($allowDelete)
                <div style="float: right;">
                    <a onclick="removeComment({{ $articleType }}, {{ $articleId }}, {{ $commentId }}); return false;" href="#" aria-label="Delete comment" title="Delete comment">
                        <x-fas-xmark class="text-red-600 h-5 w-5" />
                    </a>
                </div>
            @endif
            <div>
                {!! userAvatar($author ?? 'Deleted User', label: true) !!}
                <span class="smalldate">{{ $when?->format('j M Y H:i') }}</span>
            </div>

            <div style="word-break: break-word">
                {!! $payload !!}
            </div>
        </td>
    </tr>
@endif