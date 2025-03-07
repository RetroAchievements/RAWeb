<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Emulator;
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

        $ticket = Ticket::find($newTicketId);
        $ticket->game_hash_id = $data->gameHash->id;
        
        $emulator = Emulator::where('name', $data->emulator)->first();
        if ($emulator) {
            $ticket->emulator_id = $emulator->id;
            $ticket->emulator_version = $data->emulatorVersion;
            if (in_array($data->emulator, ['RetroArch', 'RALibRetro', 'BizHawk'], true)) {
                $ticket->emulator_core = $data->core;
            }

            $ticket->save();
        }


        return $ticket;
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

        // Combine all notes.
        if (!empty($extraNotes)) {
            $note .= "\n\n" . implode("\n", $extraNotes);
        };

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
