<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Actions\GetRankedLeaderboardEntriesAction;
use Illuminate\Http\Request;

class GetLeaderboardEntriesAction extends BaseApiAction
{
    protected int $leaderboardId;
    protected int $offset;
    protected int $count;
    protected ?User $nearUser;

    public function execute(int $leaderboardId, ?User $nearUser = null, int $offset = 0, int $count = 10): array
    {
        $this->leaderboardId = $leaderboardId;
        $this->nearUser = $nearUser;
        $this->offset = $offset;
        $this->count = $count;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['i'])) {
            return $this->missingParameters();
        }

        $this->leaderboardId = request()->integer('i', 0);
        $this->offset = request()->integer('o', 0);
        $this->count = request()->integer('c', 10);

        $username = request()->input('u');
        if ($username) {
            $this->nearUser = User::whereName($username)->first();
        }

        return null;
    }

    protected function process(): array
    {
        $leaderboard = Leaderboard::query()
            ->where('ID', $this->leaderboardId)
            ->with('developer')
            ->first();
        if (!$leaderboard) {
            return $this->resourceNotFound('leaderboard');
        }

        $totalEntries = $leaderboard->entries()->count();
        $entries = [];
        if ($this->count > 0 && $totalEntries > 0) {
            $offset = $this->offset;

            // if a nearby user was requested, attempt to center the results around that user
            if ($this->nearUser) {
                $userEntry = $leaderboard->entries(includeUnrankedUsers: true)
                    ->where('user_id', '=', $this->nearUser->id)
                    ->first();

                if ($userEntry !== null) {
                    $sharedRankEarlierEntryCount = $leaderboard->entries()
                        ->where('score', '=', $userEntry->score)
                        ->where('updated_at', '<', $userEntry->updated_at)
                        ->count();

                    $userIndex = $leaderboard->getRank($userEntry->score) + $sharedRankEarlierEntryCount;

                    $offset = $userIndex - intdiv($this->count, 2) - 1;
                    if ($offset <= 0) {
                        $offset = 0;
                    } elseif ($totalEntries - $userIndex + 1 < $this->count) {
                        $offset = max(0, $totalEntries - $this->count);
                    }
                }
            }

            // now get the entries
            $entryModels = (new GetRankedLeaderboardEntriesAction())->execute($leaderboard, $offset, $this->count);

            $offset++; // 1-based index

            foreach ($entryModels as $entry) {
                $entries[] = [
                    'User' => $entry->user->display_name,
                    'ULID' => $entry->user->ulid,
                    'AvatarUrl' => $entry->user->avatar_url,
                    'DateSubmitted' => $entry->updated_at->unix(),
                    'Score' => $entry->score,
                    'Rank' => $entry->rank,
                    'Index' => $offset++,
                ];
            }
        }

        return [
            'Success' => true,
            'LeaderboardData' => [
                'LBID' => $leaderboard->ID,
                'GameID' => $leaderboard->GameID,
                'LowerIsBetter' => $leaderboard->LowerIsBetter,
                'LBTitle' => $leaderboard->Title,
                'LBDesc' => $leaderboard->Description,
                'LBFormat' => $leaderboard->Format,
                'LBMem' => $leaderboard->Mem,
                'LBAuthor' => $leaderboard->developer?->User,
                'LBCreated' => $leaderboard->Created?->format('Y-m-d H:i:s'),
                'LBUpdated' => $leaderboard->Updated?->format('Y-m-d H:i:s'),
                'TotalEntries' => $totalEntries,
                'Entries' => $entries,
            ],
        ];
    }
}
