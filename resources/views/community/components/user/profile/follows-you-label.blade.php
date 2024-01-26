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
    <div class="h-[24px] max-w-fit flex justify-center items-center bg-embed px-1 py-0.5 border border-embed-highlight rounded">
        <p class="-mt-px">Follows you</p>
    </div>
@endif
