@props([
    'currentPage' => '',
    'parentPage' => '',
    'parentPageUrl' => '',
    'user' => null, // ?User
])

<div class='navpath'>
    <a href='/userList.php'>All Users</a>
    &raquo;
    @if (empty($currentPage))
        <span class="font-bold"><a href="{{ route('user.show', $user->display_name) }}">{{ $user->display_name }}</a></span>
    @else
        @if ($user->Deleted)
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
