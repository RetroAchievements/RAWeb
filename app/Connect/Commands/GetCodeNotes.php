<?php

declare(strict_types=1);

namespace App\Connect\Commands;

use App\Models\Game;
use App\Models\MemoryNote;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;

class GetCodeNotes extends ApiHandlerBase
{
    public int $gameId;

    public function initialize(Request $request): ?array
    {
        if (!$request->has(['g'])) {
            return $this->missingParameters();
        }

        $this->gameId = request()->integer('g', 0);

        return null;
    }

    public function process(): array
    {
        if (VirtualGameIdService::isVirtualGameId($this->gameId)) {
            [$this->gameId, $compatibility] = VirtualGameIdService::decodeVirtualGameId($this->gameId);
        }

        if (!Game::where('ID', $this->gameId)->exists()) {
            return $this->gameNotFound();
        }

        $notes = [];

        $memoryNotes = MemoryNote::where('game_id', $this->gameId)
            ->with('user')
            ->orderBy('address')
            ->get();
        foreach ($memoryNotes as $memoryNote) {
            if (!empty($memoryNote->body)) { // notes used to be deleted by setting their body to be blank
                $notes[] = [
                    'User' => $memoryNote->user->display_name ?? $memoryNote->user->User,
                    'Address' => sprintf("0x%06x", $memoryNote->address),
                    'Note' => $memoryNote->body,
                ];
            }
        }

        return [
            'Success' => true,
            'CodeNotes' => $notes,
        ];
    }
}
