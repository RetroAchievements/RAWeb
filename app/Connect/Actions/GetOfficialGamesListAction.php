<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Models\Game;
use Illuminate\Http\Request;
use stdClass;

/**
 * @deprecated
 * 
 * This action provides support for an internal API function that was only used for a short period
 * of time. It should be deprecated and eliminated, but other clients have latched onto it and
 * until they migrate to public APIs, we have to continue supporting it.
 *
 * This endpoint must be maintained indefinitely for backwards compatibility with:
 * - Batocera-based emulationstation: https://github.com/batocera-linux/batocera-emulationstation/blob/7ca0e7151b2764c73ff26f0d4689ab32d9b7abd2/es-app/src/RetroAchievements.cpp#L505
 *   which is used by other front-ends like RetroBat.
 */
class GetOfficialGamesListAction extends BaseApiAction
{
    protected int $consoleId;

    public function execute(?int $consoleId): array
    {
        $this->consoleId = $consoleId ?? 0;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        $this->consoleId = request()->integer('c', 0);

        return null;
    }

    protected function process(): array
    {
        $games = Game::query()
            ->where('achievements_published', '>', 0)
            ->when($this->consoleId > 0, function ($q) {
                $q->where('system_id', $this->consoleId);
            })
            ->orderBy('system_id')
            ->orderBy('title')
            ->select(['id', 'title'])
            ->pluck('title', 'id') // return mapping of id => title
            ->toArray();

        if (empty($games)) {
            // replace empty array with empty object so json_encode sends {} to client
            $games = new stdClass();
        }

        return [
            'Success' => true,
            'Warning' => 'This API is deprecated. Please switch to a public API.',
            'Response' => $games,
        ];
    }
}
