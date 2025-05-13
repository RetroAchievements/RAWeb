<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseApiAction;
use App\Models\GameHash;
use App\Models\System;
use Illuminate\Http\Request;
use stdClass;

class GetHashLibraryAction extends BaseApiAction
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
        $query = GameHash::compatible()
            ->select('game_hashes.md5', 'game_hashes.game_id')
            ->when($this->consoleId > 0, function ($q) {
                $q->leftJoin('GameData as gd', 'gd.ID', '=', 'game_hashes.game_id')
                    ->where('gd.ConsoleID', $this->consoleId);
            });

        $hashes = $query->pluck('game_id', 'md5')->toArray();

        if (empty($hashes)) {
            if (System::where('id', $this->consoleId)->exists()) {
                // replace empty array with empty object so json_encode sends {} to client
                $hashes = new stdClass();
            } else {
                return [
                    'Success' => false,
                    'Status' => 404,
                    'Code' => 'not_found',
                    'Error' => 'Unknown console.',
                ];
            }
        }

        return [
            'Success' => true,
            'MD5List' => $hashes,
        ];
    }
}
