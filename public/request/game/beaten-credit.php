<?php

// TODO: Remove this endpoint and refactor the beaten game credit dialog to use Livewire 3.
// Right now we don't have a great way of conditionally rendering the dialog without
// using an API endpoint or a lot of JS. The dialog content shouldn't be present in the DOM
// unless the user has explicitly attempted to open it.

use App\Enums\Permissions;
use App\Models\Game;
use App\Platform\Enums\AchievementType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Unregistered)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'id' => 'required|integer|exists:GameData,ID',
    'context' => 'nullable|string',
]);

$gameId = (int) $input['id'];
$context = isset($input['context']) ? $input['context'] : 's:|h:';

$foundGame = Game::with(['achievements' => function ($query) {
    $query->whereNotNull('type')->published()->orderBy('DisplayOrder');
}])->find($gameId);

if (!$foundGame) {
    abort(400);
}

$allTypedAchievements = $foundGame->achievements->toArray();

$progressionAchievements = [];
$winConditionAchievements = [];
foreach ($allTypedAchievements as $achievement) {
    if ($achievement['type'] === AchievementType::Progression) {
        $progressionAchievements[] = $achievement;
    } elseif ($achievement['type'] === AchievementType::WinCondition) {
        $winConditionAchievements[] = $achievement;
    }
}

return response()->json([
    'html' => Blade::render('
        <x-modal-content.beaten-game-credit
            :gameTitle="$gameTitle"
            :progressionAchievements="$progressionAchievements"
            :winConditionAchievements="$winConditionAchievements"
            :unlockContext="$unlockContext"
        />
    ', [
        'gameTitle' => $foundGame->Title,
        'progressionAchievements' => $progressionAchievements,
        'winConditionAchievements' => $winConditionAchievements,
        'unlockContext' => $context,
    ]),
]);
