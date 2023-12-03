@props([
    'targetUsername' => '',
    'parentPage' => '',
    'parentPageUrl' => '',
    'currentPage' => '',
])

<div class='navpath'>
    <a href='/userList.php'>All Users</a>
    &raquo;
    @if (empty($currentPage))
        <span class="font-bold"><a href="{{ route('user.show', $targetUsername) }}">{{ $targetUsername }}</a></span>
    @else
        <a href="{{ route('user.show', $targetUsername) }}">{{ $targetUsername }}</a>
        &raquo;
        @if (!empty($parentPage))
            <a href="{!! $parentPageUrl !!}">{{ $parentPage }}</a>
            &raquo;
        @endif
        <span class="font-bold">{{ $currentPage }}</span>
    @endif
</div>
