<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Achievement;
use App\Models\GameHash;
use App\Models\Leaderboard;
use App\Platform\Requests\StoreTriggerTicketRequest;
use Spatie\LaravelData\Data;

class StoreTriggerTicketData extends Data
{
    public function __construct(
        public Achievement|Leaderboard $ticketable,
        public string $mode,
        public int $issue,
        public string $description,
        public string $emulator,
        public ?string $emulatorVersion,
        public ?string $core,
        public GameHash $gameHash,
        public ?string $extra,
    ) {
    }

    public static function fromRequest(StoreTriggerTicketRequest $request): self
    {
        $ticketableModel = $request->ticketableModel === 'achievement' ? Achievement::class : Leaderboard::class;
        /** @var Achievement|Leaderboard $ticketable */
        $ticketable = $ticketableModel::find($request->ticketableId);

        return new self(
            ticketable: $ticketable,
            mode: $request->mode,
            issue: $request->issue,
            description: $request->description,
            emulator: $request->emulator,
            emulatorVersion: $request->emulatorVersion,
            core: $request->core,
            gameHash: GameHash::find($request->gameHashId),
            extra: $request->extra,
        );
    }
}
