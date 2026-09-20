<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\User;
use App\Platform\Enums\TicketCreationBlockReason;

class DetermineTicketCreationBlockReasonAction
{
    public function __construct(
        private readonly BuildTicketCreationDataAction $buildTicketCreationData,
    ) {
    }

    public function execute(User $user, Achievement $achievement, bool $hasSession): ?TicketCreationBlockReason
    {
        if (!$hasSession) {
            return TicketCreationBlockReason::NoPlaySession;
        }

        $ticketCreationData = $this->buildTicketCreationData->execute($achievement, $user);
        if (!count($ticketCreationData->gameHashes) || !count($ticketCreationData->emulators)) {
            return TicketCreationBlockReason::GameNotTicketable;
        }

        return null;
    }
}
