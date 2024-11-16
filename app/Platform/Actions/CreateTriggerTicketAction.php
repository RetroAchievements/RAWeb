<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Ticket;
use App\Models\User;
use App\Platform\Data\StoreTriggerTicketData;

class CreateTriggerTicketAction
{
    public function execute(StoreTriggerTicketData $data, User $user): Ticket
    {
        $note = $this->formatTicketNote($data);

        $newTicketId = _createTicket(
            user: $user,
            achievementId: $data->ticketable->id,
            reportType: $data->issue,
            hardcore: $data->mode === 'hardcore' ? 1 : 0,
            note: $note
        );

        return Ticket::find($newTicketId);
    }

    private function formatTicketNote(StoreTriggerTicketData $data): string
    {
        $note = trim($data->description);
        $extraNotes = [];

        // Add rich presence if provided.
        if ($data->extra) {
            $richPresence = $this->decodeExtra($data->extra);
            if ($richPresence) {
                $extraNotes[] = "Rich Presence at time of trigger:\n{$richPresence}";
            }
        }

        // Add hash information.
        $extraNotes[] = "RetroAchievements Hash: {$data->gameHash->md5}";

        // Add emulator information.
        $emulatorInfo = $data->emulator;
        if ($data->core && in_array($data->emulator, ['RetroArch', 'RALibRetro'], true)) {
            $emulatorInfo .= " ({$data->core})";
        }
        $extraNotes[] = "Emulator: {$emulatorInfo}";

        // Add emulator version.
        $extraNotes[] = "Emulator Version: {$data->emulatorVersion}";

        // Combine all notes.
        $note .= "\n\n" . implode("\n", $extraNotes);

        return $note;
    }

    private function decodeExtra(?string $extra): ?string
    {
        if (!$extra) {
            return null;
        }

        $decoded = json_decode(base64_decode($extra));
        if (!$decoded) {
            return null;
        }

        return $decoded->triggerRichPresence ?? null;
    }
}
