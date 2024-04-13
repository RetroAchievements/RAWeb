<?php

use App\Enums\Permissions;
use App\Models\ForumTopic;
use App\Models\Game;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($username, $permissions, $userDetails, Permissions::Registered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'topic' => 'required|integer|exists:ForumTopic,ID',
]);

$forumTopicId = $input['topic'];

/** @var ForumTopic $topic */
$foundTopic = ForumTopic::find($forumTopicId);
if ($foundTopic) {
    $foundTopic->delete();

    $foundAssociatedGame = Game::firstWhere('ForumTopicID', $forumTopicId);
    if ($foundAssociatedGame) {
        $foundAssociatedGame->ForumTopicID = null;
        $foundAssociatedGame->save();
    }
}

return back()->with('success', __('legacy.success.delete'));
