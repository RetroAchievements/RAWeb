<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Models\Game;
use Illuminate\Http\Request;

class GetGameInfosAction extends BaseApiAction
{
    protected array $gameIds;

    public function execute(array $gameIds): array
    {
        $this->gameIds = $gameIds;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['g'])) {
            return $this->missingParameters();
        }

        $this->gameIds = array_filter(explode(',', request()->input('g') ?? '', 100));

        return null;
    }

    protected function process(): array
    {
        if (empty($this->gameIds)) {
            return $this->invalidParameter("You must specify at least one game ID.");
        }

        $games = Game::whereIn('ID', $this->gameIds)
            ->select(['ID', 'Title', 'ImageIcon'])
            ->get();
        if ($games->isEmpty()) {
            return $this->resourceNotFound('games');
        }

        $response = [];
        foreach ($games as $game) {
            $response[] = [
                'ID' => $game->ID,
                'Title' => $game->Title,
                'ImageIcon' => $game->ImageIcon,
                'ImageUrl' => $game->badge_url,
            ];
        }

        return [
            'Success' => true,
            'Response' => $response,
        ];
    }
}
