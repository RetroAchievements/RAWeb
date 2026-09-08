<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Data\UserData;
use App\Models\Ticket;
use App\Platform\Enums\TicketableType;
use Carbon\Carbon;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('TicketListEntry')]
class TicketListEntryData extends Data
{
    public function __construct(
        public int $id,
        public TicketState $state,
        public TicketType $type,
        public ?bool $hardcore,
        public Carbon $createdAt,
        public ?Carbon $resolvedAt,
        public TicketableType $ticketableType,
        public int $ticketableId,
        public string $ticketableTitle,
        public ?string $ticketableBadgeUrl,
        public GameData $game,
        public ?UserData $author,
        public ?UserData $reporter,
        public ?UserData $resolver,
        public ?EmulatorData $emulator,
        public ?string $emulatorVersion,
        public ?string $emulatorCore,
        public ?GameHashData $gameHash,
    ) {
    }

    public static function fromTicket(Ticket $ticket): self
    {
        $ticketable = $ticket->getTicketableModel();

        return new self(
            id: $ticket->id,
            state: $ticket->state,
            type: $ticket->type,
            hardcore: $ticket->hardcore === null ? null : (bool) $ticket->hardcore,
            createdAt: Carbon::parse($ticket->created_at),
            resolvedAt: $ticket->resolved_at ? Carbon::parse($ticket->resolved_at) : null,
            ticketableType: TicketableType::from($ticket->ticketable_type),
            ticketableId: $ticketable->id,
            ticketableTitle: $ticketable->getTicketableTitle(),
            ticketableBadgeUrl: $ticketable->getTicketableBadgeUrl(),
            game: GameData::fromGame($ticketable->game)->include('badgeUrl', 'system.nameShort'),
            author: $ticket->author ? UserData::fromUser($ticket->author) : null,
            reporter: $ticket->reporter ? UserData::fromUser($ticket->reporter) : null,
            resolver: $ticket->resolver ? UserData::fromUser($ticket->resolver) : null,
            emulator: $ticket->emulator ? EmulatorData::fromEmulator($ticket->emulator) : null,
            emulatorVersion: $ticket->emulator_version,
            emulatorCore: $ticket->emulator_core,
            gameHash: $ticket->gameHash ? GameHashData::fromGameHash($ticket->gameHash) : null,
        );
    }
}
