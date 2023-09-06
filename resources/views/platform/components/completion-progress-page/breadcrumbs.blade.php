@props(['user' => ''])

<div class='navpath'>
    <a href='/userList.php'>All Users</a>
    &raquo;
    <a href="{{ route('user.show', $user) }}">{{ $user }}</a>
    &raquo;
    <span class="font-bold">Completion Progress</span>
</div>
