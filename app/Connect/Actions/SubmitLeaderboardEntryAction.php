<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Enums\ClientSupportLevel;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Services\UserAgentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SubmitLeaderboardEntryAction extends BaseAuthenticatedApiAction
{
    protected Leaderboard $leaderboard;
    protected int $score;
    protected ?GameHash $gameHash = null;
    protected Carbon $when;
    protected ClientSupportLevel $clientSupportLevel;

    public function execute(User $user, Leaderboard $leaderboard, int $score, ?GameHash $gameHash = null, ?Carbon $when = null): array
    {
        $this->user = $user;
        $this->leaderboard = $leaderboard;
        $this->score = $score;
        $this->gameHash = $gameHash;
        $this->when = $when ?? Carbon::now();
        $this->clientSupportLevel = ClientSupportLevel::Full;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['i', 's'])) {
            return $this->missingParameters();
        }

        $leaderboard = Leaderboard::where('id', request()->integer('i', 0))->first();
        if (!$leaderboard) {
            return $this->resourceNotFound('leaderboard');
        }
        $this->leaderboard = $leaderboard;

        $this->userAgentService = new UserAgentService();
        $this->clientSupportLevel = $this->userAgentService->getSupportLevel(request()->header('User-Agent'));
        if ($this->clientSupportLevel === ClientSupportLevel::Blocked) {
            return $this->unsupportedClient();
        }

        $this->score = request()->integer('s', 0);

        $gameHashMD5 = request()->input('m', '');
        if ($gameHashMD5) {
            $this->gameHash = GameHash::whereMd5($gameHashMD5)->first();
        }

        // ignore negative values and offsets greater than max.
        // clamping offset will invalidate validationHash.
        $maxOffset = 14 * 24 * 60 * 60; // 14 days
        $offset = min(max((int) request()->input('o', 0), 0), $maxOffset);

        $validationHash = request()->input('v', '');
        if ($offset > 0
            && strcasecmp($validationHash,
                          $this->leaderboard->submitValidationHash($this->user, $this->score, $offset)) !== 0) {

            // hash failed - ignore offset
            $offset = 0;
        }

        $this->when = Carbon::now()->subSeconds($offset);

        return null;
    }

    protected function process(): array
    {
        $this->leaderboard->loadMissing('game');
        if (!isValidConsoleId($this->leaderboard->game->system_id)) {
            return $this->unsupportedSystem('Cannot submit leaderboard entries for unsupported console.');
        }

        $retVal['Score'] = $this->score;
        $retVal['BestScore'] = $this->updateLeaderboardEntry();

        $retVal['RankInfo'] = [
            'NumEntries' => $this->leaderboard->entries()->count(),
            'Rank' => $this->leaderboard->getRank($retVal['BestScore']),
        ];

        $retVal['TopEntries'] = $this->getTopEntries();

        return [
            'Success' => true,
            'Response' => $retVal,
        ];
    }

    private function updateLeaderboardEntry(): int
    {
        $playerSession = app()->make(ResumePlayerSessionAction::class)->execute(
            $this->user,
            $this->leaderboard->game,
            ($this->gameHash && !$this->gameHash->isMultiDiscGameHash()) ? $this->gameHash : null,
            timestamp: $this->when,
        );

        // First check if there's an existing entry (including soft-deleted)
        $existingLeaderboardEntry = LeaderboardEntry::withTrashed()
            ->where('leaderboard_id', $this->leaderboard->id)
            ->where('user_id', $this->user->id)
            ->first();

        if ($existingLeaderboardEntry) {
            if (!$this->clientSupportLevel->allowsHardcoreUnlocks()) {
                $bestScore = $existingLeaderboardEntry->score;
            } elseif ($existingLeaderboardEntry->trashed()
                || $this->leaderboard->isBetterScore($this->score, $existingLeaderboardEntry->score)) {

                // Update the score first before saving/restoring to avoid race conditions with observers.
                $existingLeaderboardEntry->score = $this->score;
                $existingLeaderboardEntry->player_session_id = $playerSession->id;
                $existingLeaderboardEntry->updated_at = $this->when;

                if ($existingLeaderboardEntry->trashed()) {
                    $existingLeaderboardEntry->restore();
                } else {
                    $existingLeaderboardEntry->save();
                }

                $bestScore = $this->score;
            } else {
                // No change made.
                $bestScore = $existingLeaderboardEntry->score;
            }
        } elseif (!$this->clientSupportLevel->allowsHardcoreUnlocks()) {
            $bestScore = 0;
        } else {
            // No existing leaderboard entry. Let's insert a new one, using
            // updateOrCreate to handle potential race conditions if the client
            // is rapid-firing off submissions to the server.
            $entry = LeaderboardEntry::updateOrCreate(
                [
                    'leaderboard_id' => $this->leaderboard->id,
                    'user_id' => $this->user->id,
                ],
                [
                    'score' => $this->score,
                    'player_session_id' => $playerSession->id,
                    'created_at' => $this->when,
                    'updated_at' => $this->when,
                    'deleted_at' => null, // Ensure the entry is not soft-deleted.
                ]
            );

            $bestScore = $this->score;
        }

        return $bestScore;
    }

    private function getTopEntries(): array
    {
        $entries = $this->leaderboard->sortedEntries()
            ->with('user')
            ->limit(10)
            ->get()
            ->map(fn ($entry) => [
                'User' => $entry->user->display_name,
                'Score' => $entry->score,
                'DateSubmitted' => $entry->updated_at->unix(),
            ])
            ->toArray();

        $index = 1;
        $rank = 0;
        $score = null;
        foreach ($entries as &$entry) {
            if ($entry['Score'] !== $score) {
                $score = $entry['Score'];
                $rank = $index;
            }

            $entry['Rank'] = $rank;
            $index++;
        }

        return $entries;
    }
}
