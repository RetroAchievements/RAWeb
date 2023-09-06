@props(['targetUsername' => ''])

<div class='navpath'>
    <a href='/userList.php'>All Users</a>
    &raquo;
    <a href="{{ route('user.show', $targetUsername) }}">{{ $targetUsername }}</a>
    &raquo;
    <span class="font-bold">Completion Progress</span>
</div>
