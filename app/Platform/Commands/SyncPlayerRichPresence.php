<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\GameHashSet;
use App\Models\PlayerSession;
use App\Support\Sync\SyncTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SyncPlayerRichPresence extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:player-rich-presence {username?} {--f|full} {--p|no-post}';
    protected $description = 'Sync player rich presence';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('player_rich_presence');
    }

    protected function query(): Builder
    {
        return DB::table('UserAccounts')
            ->where('LastGameId', '>', 0)
            ->whereNotNull('RichPresenceMsgDate')
            ->select('User', 'LastGameID', 'RichPresenceMsg', 'RichPresenceMsgDate');
    }

    protected function preProcessEntity(object $origin, array $transformed): array
    {
        $message = $origin->RichPresenceMsg;
        if (empty($message)) {
            // no rich presence; ignore
            return [];
        }

        $userId = $this->getUserId($origin->User);
        if ($userId === null) {
            // unknown user; ignore
            return [];
        }

        $date = Carbon::parse($origin->RichPresenceMsgDate);

        $transformed = [
            'rich_presence' => $message,
            'rich_presence_updated_at' => $date,
        ];

        $session = PlayerSession::where('user_id', $userId)
                ->where('game_id', $origin->LastGameID)
                ->where('created_at', '<=', $date)
                ->where('updated_at', '>', $date->copy()->subHours(4))
                ->orderByDesc('created_at')
                ->first();

        if ($session) {
            // merge into existing session
            $transformed['id'] = $session->id;

            if ($date > $session->updated_at) {
                $transformed['updated_at'] = $date;
                $transformed['duration'] = $date->diffInMinutes($session->created_at);
            }
        } else {
            // create new session
            $gameHashSet = GameHashSet::where('game_id', $origin->LastGameID)->first();
            if (!$gameHashSet) {
                // cannot create session
                return [];
            }

            $transformed['user_id'] = $userId;
            $transformed['game_id'] = $origin->LastGameID;
            $transformed['game_hash_id'] = $gameHashSet->hashes()->first()->id;
            $transformed['game_hash_set_id'] = $gameHashSet->id;
            $transformed['duration'] = 0;
            $transformed['created_at'] = $date;
            $transformed['updated_at'] = $date;
        }

        return $transformed;
    }
}
