@props([
    'username' => '',
])

<?php
use App\Community\Enums\UserRelationship;
use Illuminate\Support\Facades\Auth;

$me = Auth::user() ?? null;

$areTheyFollowingMe = false;
if ($me) {
    $areTheyFollowingMe = GetFriendship($username, $me->User) === UserRelationship::Following;
}
?>

@if ($areTheyFollowingMe)
    <p class="mb-2 max-w-fit flex items-center text-2xs bg-embed px-1 py-0.5 border border-embed-highlight rounded">Follows you</p>
@endif
