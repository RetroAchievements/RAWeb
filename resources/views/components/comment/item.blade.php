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
    @if ($commentId > 0)
    <tr class="comment" id="comment_{{ $commentId }}_highlight">
    @else
    <tr class="comment">
    @endif
        <td class="align-top py-2">
            @if ($commentId > 0)
                <div class="relative">
                    <div class="absolute h-px w-px left-0" style="top: -74px;" id="comment_{{ $commentId }}"></div>
                </div>
            @endif
            @if (!$author)
                <img loading="lazy" decoding="async" width="32" height="32"
                     src="{!! media_asset('/UserPic/_User.png') !!}" class="badgeimg" />
            @elseif ($author->trashed())
                {!! userAvatar($author->User, label: false) !!}
            @else
                {!! userAvatar($author, label: false) !!}
            @endif
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
                @if (!$author)
                    <del>Unknown</del>
                @elseif ($author->trashed())
                    {!! userAvatar($author->User, label: true) !!}
                @else
                    {!! userAvatar($author, label: true) !!}
                @endif
                <span class="smalldate">{{ $when?->format('j M Y H:i') }}</span>
            </div>

            <div style="word-break: break-word">
                {!! $payload !!}
            </div>
        </td>
    </tr>
@endif