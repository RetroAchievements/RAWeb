<?php

declare(strict_types=1);

namespace App\Connect\Commands;

use App\Models\Game;
use App\Models\MemoryNote;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;

class SubmitCodeNote extends AuthenticatedApiHandlerBase
{
    public int $gameId;
    public int $address;
    public string $note;

    public function initialize(Request $request): ?array
    {
        if (!$request->has(['g', 'm', 'n'])) {
            return $this->missingParameters();
        }

        $this->gameId = request()->integer('g', 0);
        $this->address = request()->integer('m', 0);
        $this->note = request()->input('n') ?? '';

        return null;
    }

    public function process(): array
    {
        if (VirtualGameIdService::isVirtualGameId($this->gameId)) {
            [$this->gameId, $compatibility] = VirtualGameIdService::decodeVirtualGameId($this->gameId);
        }

        $memoryNote = MemoryNote::withTrashed()
            ->where('game_id', $this->gameId)
            ->where('address', $this->address)
            ->first();

        if (empty($this->note)) {
            if (!$this->user->can('delete', $memoryNote)) {
                return $this->accessDenied();
            }

            if ($memoryNote) {
                $memoryNote->delete();
            }
        } else {
            if (!$memoryNote) {
                if (!$this->user->can('create', MemoryNote::class)) {
                    return $this->accessDenied();
                }

                if (!Game::find($this->gameId)) {
                    return $this->gameNotFound();
                }

                $memoryNote = new MemoryNote([
                    'game_id' => $this->gameId,
                    'address' => $this->address,
                ]);
            } elseif ($memoryNote->trashed()) {
                if (!$this->user->can('create', MemoryNote::class)) {
                    return $this->accessDenied();
                }

                $memoryNote->restore();
            } else {
                if (!$this->user->can('update', $memoryNote)) {
                    return $this->accessDenied();
                }
            }

            $memoryNote->user_id = $this->user->id;
            $memoryNote->body = $this->note;
            $memoryNote->save();
        }

        return [
            'Success' => true,
        ];
    }
}
