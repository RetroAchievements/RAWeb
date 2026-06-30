<?php

declare(strict_types=1);

namespace App\Platform\Contracts;

use App\Models\Game;
use App\Models\User;
use App\Platform\Enums\TicketableType;
use Carbon\CarbonInterface;

interface Ticketable
{
    public function getTicketableType(): TicketableType;

    public function getTicketableGame(): Game;

    /**
     * Direct FK access.
     * This is cheaper than `getTicketableGame()` when only the id is needed.
     */
    public function getTicketableGameId(): int;

    public function getTicketableAssignee(?CarbonInterface $at = null): ?User;

    public function getTicketableTitle(): string;

    public function getTicketableUrl(): string;

    public function getTicketableIconUrl(): string;

    public function getTicketableBadgeUrl(): ?string;

    public function demoteForTicket(User $byUser): void;
}
