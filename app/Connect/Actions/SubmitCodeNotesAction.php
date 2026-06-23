<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Models\Game;
use App\Models\MemoryNote;
use App\Models\User;
use App\Platform\Services\VirtualGameIdService;
use Illuminate\Http\Request;

class SubmitCodeNotesAction extends BaseAuthenticatedApiAction
{
    private const MAX_NOTES_PER_REQUEST = 500;

    protected int $gameId;
    protected array $notes;

    public function execute(int $gameId, array $notes, User $user): array
    {
        $this->gameId = $gameId;
        $this->notes = $notes;
        $this->user = $user;

        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        if (!$request->has(['g', 'n'])) {
            return $this->missingParameters();
        }

        // Reject callers without proper perms before parsing.
        // Without this, an unauthenticated user could still force the server
        // to parse a multi-MB payload and build a wide `IN()`.
        if (!$this->user->can('create', MemoryNote::class)) {
            return $this->mustBeDeveloper();
        }

        $this->notes = [];
        $this->gameId = request()->integer('g', 0);
        $notes = request()->input('n') ?? '';
        foreach (explode("\n", $notes) as $line) {
            if (empty($line)) {
                continue;
            }

            if (count($this->notes) >= self::MAX_NOTES_PER_REQUEST) {
                return $this->invalidParameter('Too many notes in a single request.');
            }

            $index = strpos($line, ':');
            if ($index === false) {
                return $this->invalidParameter('Improperly encoded notes list.');
            }

            $address = intval(substr($line, 0, $index));
            $note = stripcslashes(substr($line, $index + 1));
            $this->notes[$address] = $note;
        }

        return null;
    }

    protected function process(): array
    {
        if (VirtualGameIdService::isVirtualGameId($this->gameId)) {
            [$this->gameId, $compatibility] = VirtualGameIdService::decodeVirtualGameId($this->gameId);
        }

        if (!Game::where('id', $this->gameId)->exists()) {
            return $this->gameNotFound();
        }

        $memoryNotes = MemoryNote::withTrashed()
            ->where('game_id', $this->gameId)
            ->whereIn('address', array_keys($this->notes))
            ->get();

        $canCreate = $this->user->can('create', MemoryNote::class);

        $accessDenied = [];
        $successful = [];
        foreach ($this->notes as $address => $note) {
            $memoryNote = $memoryNotes->where('address', $address)->first();

            if (empty($note)) {
                if (!$this->user->can('delete', $memoryNote)) {
                    $accessDenied[] = $address;
                    continue;
                }

                if ($memoryNote) {
                    $memoryNote->delete();
                }
            } else {
                if (!$memoryNote) {
                    if (!$canCreate) {
                        $accessDenied[] = $address;
                        continue;
                    }

                    $memoryNote = new MemoryNote([
                        'game_id' => $this->gameId,
                        'address' => $address,
                    ]);
                } elseif ($memoryNote->trashed()) {
                    if (!$canCreate) {
                        $accessDenied[] = $address;
                        continue;
                    }

                    $memoryNote->restore();
                } else {
                    if (!$this->user->can('update', $memoryNote)) {
                        $accessDenied[] = $address;
                        continue;
                    }
                }

                $memoryNote->user_id = $this->user->id;
                $memoryNote->body = $note;
                $memoryNote->save();
            }

            $successful[] = $address;
        }

        if (!empty($accessDenied)) {
            $result = $this->accessDenied();
            $result['SuccessfulAddresses'] = $successful;
            $result['AccessDeniedAddresses'] = $accessDenied;

            return $result;
        }

        return [
            'Success' => true,
            'SuccessfulAddresses' => $successful,
        ];
    }
}
