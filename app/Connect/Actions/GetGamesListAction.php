<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Models\Game;
use App\Models\System;
use Illuminate\Http\Request;
use stdClass;

/**
 * @deprecated
 *
 * This action provides support for the legacy API function used to get a list of
 * games for a system.
 * New clients should use ?r=systemgames instead (available since rcheevos 12.4) which
 * includes achievement counts, badge infomation, and hashes as part of the response.
 *
 * This endpoint must be maintained until the minimum DLL version exceeds 1.4.2.
 */
class GetGamesListAction extends BaseApiAction
{
    protected int $consoleId;

    public function execute(?int $consoleId): array
    {
        $this->consoleId = $consoleId ?? 0;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!request()->has('c')) {
            return $this->missingParameters();
        }

        $this->consoleId = request()->integer('c', 0);

        return null;
    }

    protected function process(): array
    {
        if (!System::where('id', $this->consoleId)->exists()) {
            return $this->resourceNotFound('system');
        }

        $games = Game::query()
            ->where('system_id', $this->consoleId)
            ->orderBy('sort_title')
            ->select(['id', 'title'])
            ->pluck('title', 'id') // return mapping of id => title
            ->toArray();

        if (empty($games)) {
            // replace empty array with empty object so json_encode sends {} to client
            $games = new stdClass();
        }

        return [
            'Success' => true,
            'Response' => $games,
        ];
    }
}
