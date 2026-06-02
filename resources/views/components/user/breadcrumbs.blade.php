@props([
    'currentPage' => '',
    'parentPage' => '',
    'parentPageUrl' => '',
    'user' => null, // ?User
])

<div class='navpath'>
    @if (empty($currentPage))
        <span class="font-bold">{{ $user->display_name }}</span>
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
