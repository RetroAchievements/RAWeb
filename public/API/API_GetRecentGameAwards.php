<?php

declare(strict_types=1);

/*
 *  API_GetRecentGameAwards - gets a list of all beaten and mastery awards granted across the userbase, ordered by date
 *    d : starting date (YYYY-MM-DD) (default: now)
 *    o : offset - number of entries to skip (default: 0)
 *    c : count - number of entries to return (default: 25, maximum: 100)
 *    k : desired game award kinds, can be a comma-separated list.
 *        possible values are "beaten-softcore", "beaten-hardcore", "completed", and "mastered". (default: all)
 *
 *  int         Count                       number of game awards returned in the response
 *  int         Total                       number of game awards total which satisfy the filter criteria
 *  array       Results
 *   object      [value]
 *    string      User                      player who earned the award
 *    string      ULID                      queryable stable unique identifier of the player who earned the award
 *    string      AwardKind                 "mastered', "completed", "beaten-hardcore", or "beaten-softcore"
 *    datetime    AwardDate                 an ISO8601 timestamp string for when the award was granted
 *    int         GameID                    unique identifier of the game
 *    string      GameTitle                 title of the game
 *    int         ConsoleID                 unique identifier of the console associated to the game
 *    string      ConsoleName               name of the console associated to the game
 */

use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\System;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

$input = Validator::validate(Arr::wrap(request()->query()), [
    'd' => ['sometimes', 'date', 'before_or_equal:today'],
    'o' => ['sometimes', 'integer', 'min:0', 'nullable'],
    'c' => ['sometimes', 'integer', 'min:1'],
    'k' => ['sometimes', 'string'],
]);

$targetDate = $input['d'] ?? null;
$offset = $input['o'] ?? 0;
$count = $input['c'] ?? 25;
$awardKindsCsv = $input['k'] ?? '';

// If $count exceeds 100, force it down to 100.
$count = min($count, 100);

// Filter by given award kinds.
$validAwardKinds = ['beaten-softcore', 'beaten-hardcore', 'completed', 'mastered'];
$awardKinds = explode(',', $awardKindsCsv);
$awardKinds = array_filter($awardKinds, fn ($kind) => in_array($kind, $validAwardKinds, true));

// If award kinds are provided but they're all invalid, return 0 records.
if (!empty($awardKindsCsv) && empty($awardKinds)) {
    return response()->json([
        'Count' => 0,
        'Total' => 0,
        'Results' => [],
    ]);
}

// Construct the initial base query, which pulls beaten and mastery awards.
$baseQuery = PlayerBadge::with('user')->where(function ($query) {
    $query->where('award_type', AwardType::Mastery)
        ->orWhere('award_type', AwardType::GameBeaten);
});

// If the consumer is trying to filter by a start date, add that filtering to the query.
if ($targetDate !== null) {
    $baseQuery->whereDate('awarded_at', '<=', $targetDate);
}

// If the consumer is trying to filter by specific award kinds, add that filtering to the query.
$kindMapping = [
    'beaten-softcore' => ['award_type' => AwardType::GameBeaten, 'award_tier' => false],
    'beaten-hardcore' => ['award_type' => AwardType::GameBeaten, 'award_tier' => true],
    'completed' => ['award_type' => AwardType::Mastery, 'award_tier' => false],
    'mastered' => ['award_type' => AwardType::Mastery, 'award_tier' => true],
];
if (!empty($awardKinds)) {
    $baseQuery->where(function ($query) use ($awardKinds, $kindMapping) {
        foreach ($awardKinds as $awardKind) {
            if (isset($kindMapping[$awardKind])) {
                $mapping = $kindMapping[$awardKind];
                $query->orWhere(function ($q) use ($mapping) {
                    $q->where('award_type', $mapping['award_type'])
                        ->where('award_tier', $mapping['award_tier']);
                });
            }
        }
    });
}

// Now, actually make the fetch, which includes both a limit and the offset.
$fetchedGameAwards = (clone $baseQuery)->orderBy('awarded_at', 'desc')
    ->skip($offset)
    ->limit($count)
    ->get();

$gameAwardGameIds = $fetchedGameAwards->pluck('award_key')->unique()->filter();
$associatedGames = Game::with('system')->whereIn('id', $gameAwardGameIds)
    ->get()
    ->keyBy('id');

$systemIds = $associatedGames->pluck('system_id')->unique()->filter();
$associatedSystems = System::whereIn('id', $systemIds)->get(['id', 'name'])->keyBy('id');

$mappedGameAwards = $fetchedGameAwards
    ->filter(fn ($gameAward) => $gameAward->user !== null)
    ->map(function ($gameAward) use ($associatedGames, $associatedSystems) {
        $associatedGame = $associatedGames->get($gameAward->award_key);

        $awardKind = $gameAward->award_type === AwardType::GameBeaten
            ? ($gameAward->award_tier ? 'beaten-hardcore' : 'beaten-softcore')
            : ($gameAward->award_tier ? 'mastered' : 'completed');

        $mappedAward = [
            'User' => $gameAward->user->display_name,
            'ULID' => $gameAward->user->ulid,
            'AwardKind' => $awardKind,
            'AwardDate' => $gameAward->awarded_at->toIso8601String(),
            'GameID' => $gameAward->award_key,
            'GameTitle' => $associatedGame->title ?? null,
            'ConsoleID' => $associatedGame->system_id ?? null,
            'ConsoleName' => $associatedSystems[$associatedGame->system_id]->name ?? null,
        ];

        return $mappedAward;
    });

return response()->json([
    'Count' => $mappedGameAwards->count(),
    'Total' => (clone $baseQuery)->count(),
    'Results' => $mappedGameAwards->toArray(),
]);
