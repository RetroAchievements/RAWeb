@props([
    'friendCount' => 0,
])

<div class="component">
    <h3>Followed Users Ranking</h3>

    @if ($friendCount === 0)
        <p>
            You don't appear to be following anyone yet. Why not
            <a href="{{ url('userList.php') }}">browse the user pages</a>
            to find someone to follow?
        </p>
    @else
        <p>You're following {{ localized_number($friendCount) }} other players.</p>
        <p>
            <a href="{{ url('friends.php') }}">Visit your Following page</a> to see how you
            compare in daily, weekly, and monthly points.
        </p>
    @endif
</div>