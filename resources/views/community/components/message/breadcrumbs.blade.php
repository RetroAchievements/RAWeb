@props([
    'targetUsername' => '',
    'currentPage' => '',
])

<div class='navpath'>
    <a href='/inbox.php'>Messages</a>
    &raquo;
    <span class="font-bold">{{ $currentPage }}</span>
</div>
