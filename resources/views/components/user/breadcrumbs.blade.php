@props([
    'currentPage' => '',
    'parentPage' => '',
    'parentPageUrl' => '',
    'user' => null, // ?User
])

<div class='navpath'>
    @if (empty($currentPage))
        @if ($user->deleted_at)
            <span class="line-through font-bold">{{ $user->display_name }}</span>
        @else
            <span class="font-bold">{{ $user->display_name }}</span>
        @endif
    @else
        @if ($user->deleted_at)
            <span class="line-through">{{ $user->display_name }}</span>
        @else
            <a href="{{ route('user.show', $user->display_name) }}">{{ $user->display_name }}</a>
        @endif
        &raquo;
        @if (!empty($parentPage))
            <a href="{!! $parentPageUrl !!}">{{ $parentPage }}</a>
            &raquo;
        @endif
        <span class="font-bold">{{ $currentPage }}</span>
    @endif
</div>
