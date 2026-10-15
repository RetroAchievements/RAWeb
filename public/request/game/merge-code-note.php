<?php

use App\Enums\Permissions;
use App\Models\GameAchievementSet;
use App\Models\MemoryNote;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'gameId' => 'required|integer',
    'address' => 'required|integer',
    'keep' => 'required|integer|min:0|max:1',
]);

if ($input['keep']) {
    // keep subset note; overwrite base set note with subset note data
    $subsetNote = MemoryNote::where('game_id', $input['gameId'])
        ->where('address', $input['address'])
        ->first();

    if (!$subsetNote) {
        $success = false;
    } else {
        $subset = GameAchievementSet::query()
            ->whereIn('achievement_set_id',
                GameAchievementSet::where('game_id', $input['gameId'])
                    ->where('type', AchievementSetType::Core)
                    ->pluck('achievement_set_id')
            )
            ->where('type', '!=', AchievementSetType::Core)
            ->first();

        if (!$subset) {
            $success = false;
        } else {
            $note = MemoryNote::withTrashed()
                ->where('game_id', $subset->game_id)
                ->where('address', $input['address'])
                ->orderBy('deleted_at')
                ->first();

            if (!$note) {
                $note = new MemoryNote([
                    'game_id' => $subset->game_id,
                    'address' => $input['address'],
                ]);
            } elseif ($note->trashed()) {
                $note->restore();
            }

            $note->user_id = $subsetNote->user_id;
            $note->body = $subsetNote->body;
            $success = $note->save();

            $subsetNote->delete();
        }
    }
} else {
    // keep base set note; delete subset note
    $success = MemoryNote::where('game_id', $input['gameId'])
        ->where('address', $input['address'])
        ->delete();
}

if (!$success) {
    abort($result['Status'] ?? 400);
}

return response()->json(['message' => __('legacy.success.ok')]);
