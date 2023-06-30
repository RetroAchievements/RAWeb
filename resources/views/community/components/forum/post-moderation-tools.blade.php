<?php
use App\Community\Enums\UserAction;

$updateForumPostPermissions = UserAction::UpdateForumPostPermissions;
?>

@props([
    'commentAuthor',
])

<form
    action='/request/user/update.php'
    method='post'
    onsubmit="return confirm('Authorise this user and all their posts?')"
>
    {{ csrf_field() }}
    <input type='hidden' name='property' value="{{ $updateForumPostPermissions }}" />
    <input type='hidden' name='target' value="{{ $commentAuthor }}" />
    <input type='hidden' name='value' value='1' />
    <button class='btn p-1 lg:text-xs'>Authorise</button>
</form>

<form
    action='/request/user/update.php'
    method='post'
    onsubmit="return confirm('Permanently Block (spam)?')"
>
    {{ csrf_field() }}
    <input type='hidden' name='property' value="{{ $updateForumPostPermissions }}" />
    <input type='hidden' name='target' value="{{ $commentAuthor }}" />
    <input type='hidden' name='value' value='0' />
    <button class='btn btn-danger p-1 lg:text-xs'>Block</button>
</form>