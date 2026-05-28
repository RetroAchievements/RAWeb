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

    public function getTicketableAssignee(?CarbonInterface $at = null): ?User;

    public function getTicketableTitle(): string;

    public function getTicketableUrl(): string;

    public function getTicketableBadgeUrl(): ?string;
}
