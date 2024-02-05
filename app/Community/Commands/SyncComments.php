<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Models\News;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
use App\Support\Sync\SyncTrait;
use Exception;
use Illuminate\Console\Command;

class SyncComments extends Command
{
    use SyncTrait;

    protected $signature = 'ra:sync:comments {--f|full} {--p|no-post}';

    protected $description = 'Sync comments';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function handle(): void
    {
        $this->sync('comments');
    }

    protected function preProcessEntity(object $origin, array $transformed): array
    {
        $payload = null;

        return $transformed;

        // @phpstan-ignore-next-line
        dd($origin);

        /**
         * cleanup
         */
        $payload = $origin->Payload;
        $payload = trim($payload);

        $commentableType = null;
        switch ($origin->ArticleType) {
            case 1:
                $commentableType = resource_type(Game::class);
                break;
            case 2:
                $commentableType = resource_type(Achievement::class);
                break;
            case 3:
                $commentableType = resource_type(User::class);
                break;
            case 4:
                $commentableType = resource_type(News::class);
                break;
            case 5:
                // skip activity comments
                // $commentableType = resource_type(Activity::class);
                break;
            case 6:
                $commentableType = resource_type(Leaderboard::class);
                break;
        }

        if (empty($commentableType)) {
            return [];
        }

        return $transformed;
        // [
        //     'commentable_id' => $origin->id,
        //     'commentable_type' => $commentableType,
        //     'user_id' => $origin->ExistingAuthorID,
        // ];

        // $payload = \str_replace("\n\n", "\n", $payload);
        // DB::connection('mysql')->table('comments')
        //     ->insertUpdate([
        //         'commentable_id' => $transformed->ArticleID,
        //         'commentable_type' => $commentableType,
        //         'user_id' => $transformed->AuthorID,
        //         'body' => $payload,
        //         'created_at' => $transformed->DateCreated,
        //         'updated_at' => $transformed->DateModified,
        //     ]);
    }
}
